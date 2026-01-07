<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $invoice->document_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 14px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }

        .invoice-info {
            text-align: right;
            flex: 1;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }

        .invoice-number {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .customer-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-weight: bold;
            font-size: 16px;
            color: #007bff;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .customer-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background-color: #007bff;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
        }

        .items-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-section {
            float: right;
            width: 300px;
            margin-top: 20px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #ddd;
        }

        .totals-table .label {
            font-weight: bold;
            text-align: right;
        }

        .totals-table .amount {
            text-align: right;
            width: 120px;
        }

        .total-row {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .notes-section {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft { background-color: #6c757d; color: white; }
        .status-sent { background-color: #007bff; color: white; }
        .status-paid { background-color: #28a745; color: white; }
        .status-overdue { background-color: #dc3545; color: white; }
        .status-cancelled { background-color: #6c757d; color: white; }

        .tax-breakdown {
            font-size: 11px;
            color: #666;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-name">
               {{ $company['company_name'] ?? 'Optiflow' }}
            </div>
            <div>RNC: 123-45678-9</div>
            <div>Teléfono: (809) 123-4567</div>
            <div>Email: testtt</div>
            <div>Dirección:testtt.</div>
        </div>

        <div class="invoice-info">
            <div class="invoice-title">FACTURA</div>
            <div class="invoice-number">{{ $invoice->document_number }}</div>
            <div><strong>Fecha de emisión:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</div>
            <div><strong>Fecha de vencimiento:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</div>
            <div style="margin-top: 10px;">
                <span class="status-badge status-{{ $invoice->status->value }}">
                   {{ $invoice->status->label() }}
                </span>
            </div>
        </div>
    </div>

    <div class="customer-section">
        <div class="section-title">INFORMACIÓN DEL CLIENTE</div>
        <div class="customer-info">
            <div><strong>{{ $invoice->contact->name }}</strong></div>
            @if($invoice->contact->identification_number)
                <div>{{ $invoice->contact->identification_type ?? 'RNC' }}: {{ $invoice->contact->identification_number }}</div>
            @endif
            @if($invoice->contact->email)
                <div>Email: {{ $invoice->contact->email }}</div>
            @endif
            @if($invoice->contact->phone_primary)
                <div>Teléfono: {{ $invoice->contact->phone_primary }}</div>
            @endif
            @if($invoice->contact->full_address)
                <div>Dirección: {{ $invoice->contact->full_address }}</div>
            @endif
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Descripción</th>
                <th style="width: 10%;" class="text-center">Cant.</th>
                <th style="width: 15%;" class="text-right">Precio Unit.</th>
                <th style="width: 10%;" class="text-center">Descuento</th>
                <th style="width: 10%;" class="text-center">Impuestos</th>
                <th style="width: 15%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-center text-xs">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">RD$ {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-center">RD$ {{ number_format($item->discount_amount ?? 0, 2) }}</td>
                    <td class="text-center">
                        @php
                            $itemTaxTotal = 0;
                            if($item->taxes) {
                                foreach($item->taxes as $tax) {
                                    $itemTaxTotal += $tax->pivot->amount ?? 0;
                                }
                            }
                        @endphp
                        RD$ {{ number_format($itemTaxTotal, 2) }}
                    </td>
                    <td class="text-right font-bold">RD$ {{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="amount">RD$ {{ number_format($invoice->subtotal_amount, 2) }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
                <tr>
                    <td class="label">Descuento:</td>
                    <td class="amount">-RD$ {{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
            @endif
            @php
                $taxBreakdown = [];
                foreach($invoice->items as $item) {
                    if($item->taxes) {
                        foreach($item->taxes as $tax) {
                            $taxName = $tax->name;
                            $taxAmount = $tax->pivot->amount ?? 0;
                            if (!isset($taxBreakdown[$taxName])) {
                                $taxBreakdown[$taxName] = 0;
                            }
                            $taxBreakdown[$taxName] += $taxAmount;
                        }
                    }
                }
            @endphp
            @foreach($taxBreakdown as $taxName => $taxAmount)
                <tr>
                    <td class="label">{{ $taxName }}:</td>
                    <td class="amount">RD$ {{ number_format($taxAmount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="label">TOTAL:</td>
                <td class="amount">RD$ {{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($invoice->notes)
        <div class="notes-section">
            <div class="section-title">NOTAS</div>
            <div>{{ $invoice->notes }}</div>
        </div>
    @endif

    <div class="footer">
        <div>Gracias por su preferencia</div>
        <div>Factura generada el {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>
