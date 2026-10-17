<?php

namespace App\Services;

use App\DTOs\CreateInvoiceDTO;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceCreationService
{
    private string $issuerTaxId;

    public function __construct()
    {
        $this->issuerTaxId = (string) config('fiscal.issuer_tax_id', 'B12345678');
    }

    public function create(CreateInvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($dto) {
            $now = Carbon::now();
            $issueDate = $now->format('d-m-Y');
            $isoTimestamp = $now->toIso8601String();

            // Bloqueo pesimista para evitar colisiones concurrentes
            $previousInvoice = Invoice::where('series', $dto->series)
                ->lockForUpdate()
                ->orderByDesc('number')
                ->first();

            $nextNumber = $previousInvoice ? ($previousInvoice->number + 1) : 1;
            $previousHash = $previousInvoice?->chain_hash;

            // Procesar líneas y calcular desglose de IVA
            $taxBases = [];
            $processedLines = [];
            $totalTaxBase = 0.00;
            $totalVatAmount = 0.00;

            foreach ($dto->lines as $index => $line) {
                $subtotal = round($line['quantity'] * $line['unit_price'], 2);
                $vatRate = round($line['vat_rate'], 2);

                $processedLines[] = [
                    'line_number' => $index + 1,
                    'concept' => $line['concept'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $subtotal,
                    'vat_rate' => $vatRate,
                ];

                $taxBases[(string)$vatRate] = round(($taxBases[(string)$vatRate] ?? 0.00) + $subtotal, 2);
                $totalTaxBase += $subtotal;
            }

            $taxBreakdowns = [];
            foreach ($taxBases as $rateStr => $base) {
                $rate = (float)$rateStr;
                $vatAmount = round($base * ($rate / 100), 2);
                $totalVatAmount += $vatAmount;

                $taxBreakdowns[] = [
                    'tax_type' => '01', // IVA
                    'tax_rate' => $rate,
                    'tax_base' => $base,
                    'tax_amount' => $vatAmount,
                ];
            }

            $totalInvoice = round($totalTaxBase + $totalVatAmount, 2);

            // Generar huella SHA-256 según VeriFactu
            $formattedNumSerie = $dto->series . '/' . $nextNumber;
            $chainHash = $this->calculateVerifactuHash(
                issuerTaxId: $this->issuerTaxId,
                numSerie: $formattedNumSerie,
                issueDate: $issueDate,
                invoiceType: $dto->invoiceType,
                vatAmount: $totalVatAmount,
                totalAmount: $totalInvoice,
                previousHash: $previousHash,
                isoTimestamp: $isoTimestamp
            );

            $qrPayload = $this->generateQrPayload(
                issuerTaxId: $this->issuerTaxId,
                numSerie: $formattedNumSerie,
                issueDate: $issueDate,
                totalAmount: $totalInvoice,
                chainHash: $chainHash
            );

            // Registrar cabecera
            $invoice = Invoice::create([
                'series' => $dto->series,
                'number' => $nextNumber,
                'invoice_type' => $dto->invoiceType,
                'issue_date' => $now->toDateString(),
                'issue_time' => $now->toTimeString(),
                'issuer_tax_id' => $this->issuerTaxId,
                'client_id' => $dto->clientId,
                'rectified_invoice_id' => $dto->rectifiedInvoiceId,
                'rectification_reason' => $dto->rectificationReason,
                'total_tax_base' => $totalTaxBase,
                'total_tax_amount' => $totalVatAmount,
                'total_invoice' => $totalInvoice,
                'previous_invoice_id' => $previousInvoice?->id,
                'previous_hash' => $previousHash,
                'chain_hash' => $chainHash,
                'qr_payload' => $qrPayload,
                'created_by_user_id' => $dto->createdByUserId,
            ]);

            // Registrar líneas y desgloses
            foreach ($processedLines as $lineData) {
                $invoice->lines()->create($lineData);
            }

            foreach ($taxBreakdowns as $taxData) {
                $invoice->taxBreakdowns()->create($taxData);
            }

            return $invoice;
        });
    }

    private function calculateVerifactuHash(
        string $issuerTaxId,
        string $numSerie,
        string $issueDate,
        string $invoiceType,
        float $vatAmount,
        float $totalAmount,
        ?string $previousHash,
        string $isoTimestamp
    ): string {
        $cleanPrevHash = $previousHash ? strtoupper($previousHash) : '';

        $canonicalString = implode('&', [
            'IDEmisorFactura=' . strtoupper($issuerTaxId),
            'NumSerieFactura=' . $numSerie,
            'FechaExpedicionFactura=' . $issueDate,
            'TipoFactura=' . $invoiceType,
            'CuotaTotal=' . number_format($vatAmount, 2, '.', ''),
            'ImporteTotal=' . number_format($totalAmount, 2, '.', ''),
            'Huella=' . $cleanPrevHash,
            'FechaHoraHusoGenRegistro=' . $isoTimestamp,
        ]);

        return strtoupper(hash('sha256', $canonicalString));
    }

    private function generateQrPayload(
        string $issuerTaxId,
        string $numSerie,
        string $issueDate,
        float $totalAmount,
        string $chainHash
    ): string {
        $baseAeatUrl = 'https://sede.agenciatributaria.gob.es/Sede/procedimientoini/ZZ01.shtml';

        $params = http_build_query([
            'nif' => strtoupper($issuerTaxId),
            'numserie' => $numSerie,
            'fecha' => $issueDate,
            'importe' => number_format($totalAmount, 2, '.', ''),
            'huella' => substr($chainHash, 0, 16),
        ]);

        return $baseAeatUrl . '?' . $params;
    }
}