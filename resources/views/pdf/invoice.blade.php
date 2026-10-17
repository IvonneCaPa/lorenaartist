<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $invoice->series }}-{{ $invoice->number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        .box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-top: 15px;
            margin-bottom: 20px;
        }
        .items-table {
            margin-top: 15px;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #2c3e50;
            color: #ffffff;
            padding: 8px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        .totals-table {
            width: 45%;
            margin-left: auto;
            margin-bottom: 25px;
        }
        .totals-table td {
            padding: 5px 8px;
        }
        .total-highlight {
            font-size: 14px;
            background-color: #f1f3f5;
            border-top: 2px solid #2c3e50;
        }
        .verifactu-container {
            border-top: 1px solid #ccc;
            padding-top: 15px;
            margin-top: 20px;
        }
        .qr-code {
            width: 110px;
            text-align: center;
            vertical-align: middle;
        }
        .legal-notice {
            font-size: 9px;
            color: #6c757d;
            padding-left: 15px;
            vertical-align: middle;
        }
        .rectification-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 8px;
            border-radius: 4px;
            margin-top: 15px;
            font-size: 11px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="invoice-title">{{ config('fiscal.issuer_name', 'Centro de Estética') }}</div>
                <div><strong>NIF/CIF:</strong> {{ $invoice->issuer_tax_id }}</div>
                <div>Barcelona, España</div>
            </td>
            <td style="width: 45%;" class="text-right">
                <div class="invoice-title">
                    {{ str_starts_with($invoice->invoice_type, 'R') ? 'FACTURA RECTIFICATIVA' : 'FACTURA' }}
                </div>
                <div><strong>Serie/Número:</strong> {{ $invoice->series }}-{{ str_pad($invoice->number, 4, '0', STR_PAD_LEFT) }}</div>
                <div><strong>Fecha expedición:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</div>
                <div><strong>Hora:</strong> {{ $invoice->issue_time }}</div>
                <div><strong>Tipo:</strong> {{ $invoice->invoice_type }}</div>
            </td>
        </tr>
    </table>

    @if($invoice->rectified_invoice_id && $invoice->rectifiedInvoice)
    <div class="rectification-box">
        <strong>Documento rectificativo:</strong> Rectifica a la factura 
        <strong>{{ $invoice->rectifiedInvoice->series }}-{{ str_pad($invoice->rectifiedInvoice->number, 4, '0', STR_PAD_LEFT) }}</strong> 
        con fecha {{ \Carbon\Carbon::parse($invoice->rectifiedInvoice->issue_date)->format('d/m/Y') }}. 
        Motivo AEAT: Código {{ $invoice->rectification_reason }}.
    </div>
    @endif

    <div class="box">
        <strong>DATOS DEL DESTINATARIO</strong><br>
        @if($invoice->client)
            <strong>Nombre:</strong> {{ $invoice->client->name }}<br>
            <strong>NIF/NIE:</strong> {{ $invoice->client->tax_id ?? 'No indicado / Venta menor' }}<br>
            @if($invoice->client->address)
                <strong>Dirección:</strong> {{ $invoice->client->address }}, {{ $invoice->client->postal_code }} {{ $invoice->client->city }}
            @endif
        @else
            <em>Venta directa mostrador</em>
        @endif
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 50%;">Concepto / Servicio</th>
                <th style="width: 15%;" class="text-right">Cant.</th>
                <th style="width: 15%;" class="text-right">Precio Ud.</th>
                <th style="width: 15%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
            <tr>
                <td class="text-center">{{ $line->line_number }}</td>
                <td>{{ $line->concept }}</td>
                <td class="text-right">{{ number_format($line->quantity, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($line->unit_price, 2, ',', '.') }} €</td>
                <td class="text-right">{{ number_format($line->subtotal, 2, ',', '.') }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        @foreach($invoice->taxBreakdowns as $tax)
        <tr>
            <td>Base Imponible (IVA {{ number_format($tax->tax_rate, 0) }}%):</td>
            <td class="text-right">{{ number_format($tax->tax_base, 2, ',', '.') }} €</td>
        </tr>
        <tr>
            <td>Cuota IVA ({{ number_format($tax->tax_rate, 0) }}%):</td>
            <td class="text-right">{{ number_format($tax->tax_amount, 2, ',', '.') }} €</td>
        </tr>
        @endforeach
        <tr class="total-highlight">
            <td class="bold">TOTAL FACTURA:</td>
            <td class="text-right bold">{{ number_format($invoice->total_invoice, 2, ',', '.') }} €</td>
        </tr>
    </table>

    <div class="verifactu-container">
        <table>
            <tr>
                <td class="qr-code">
                    <img src="data:image/svg+xml;base64,{{ $qrBase64 }}" alt="QR Fiscal" width="95" height="95">
                </td>
                <td class="legal-notice">
                    <strong>Factura verificable en la sede electrónica de la AEAT / VeriFactu</strong><br>
                    <span><strong>Huella SHA-256:</strong> {{ $invoice->chain_hash }}</span><br>
                    <span>Registro informático inalterable de conformidad con el RD 1007/2023.</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>