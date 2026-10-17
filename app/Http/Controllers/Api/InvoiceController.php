<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateInvoiceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceCreationService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::with(['client', 'taxBreakdowns'])
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json($invoices, 200);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $dto = new CreateInvoiceDTO(
            series: $request->validated('series'),
            invoiceType: $request->validated('invoice_type'),
            clientId: $request->validated('client_id'),
            lines: $request->validated('lines'),
            createdByUserId: $request->user()->id,
            rectifiedInvoiceId: $request->validated('rectified_invoice_id'),
            rectificationReason: $request->validated('rectification_reason')
        );

        $invoice = $this->invoiceService->create($dto);

        return response()->json([
            'message' => 'Factura registrada y sellada con éxito.',
            'data' => $invoice,
        ], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json([
            'data' => $invoice->load(['lines', 'taxBreakdowns', 'client']),
        ], 200);
    }
}