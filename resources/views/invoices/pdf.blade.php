<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $invoice->document_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.4;
            color: #1a1a2e;
            padding: 40px 40px 30px;
        }

        /* ── Header ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .header-table td {
            vertical-align: top;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 2px;
        }

        .company-subtitle {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 4px;
        }

        .company-detail {
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }

        .company-detail strong {
            font-weight: bold;
        }

        .invoice-type {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a2e;
            text-align: right;
        }

        .ncf-number {
            font-size: 20px;
            font-weight: bold;
            color: #1a1a2e;
            text-align: right;
            margin: 4px 0;
        }

        .invoice-meta {
            font-size: 12px;
            color: #333;
            text-align: right;
            line-height: 1.6;
        }

        .logo-cell {
            width: 120px;
            text-align: center;
            vertical-align: middle;
            padding-right: 15px;
        }

        .logo-cell img {
            max-height: 80px;
            max-width: 110px;
        }

        /* ── Client section ── */
        .client-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .client-table td {
            vertical-align: top;
            padding-top: 5px;
        }

        .client-label {
            font-size: 13px;
            color: #1a1a2e;
        }

        .client-label strong {
            font-weight: bold;
        }

        .amount-due-label {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a2e;
            text-align: right;
        }

        .amount-due-value {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a2e;
            text-align: right;
        }

        /* ── Items table ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .items-table thead th {
            background-color: #f0f0f5;
            color: #1a1a2e;
            font-weight: bold;
            font-size: 12px;
            padding: 10px 8px;
            text-align: left;
            border-bottom: 2px solid #d0d0d8;
        }

        .items-table tbody td {
            padding: 12px 8px;
            font-size: 12px;
            border-bottom: 1px solid #e8e8ec;
            vertical-align: middle;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #d0d0d8;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .line-count {
            font-size: 13px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 25px;
        }

        /* ── Bottom section (notes + totals) ── */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .bottom-table > tbody > tr > td {
            vertical-align: top;
        }

        .notes-cell {
            width: 55%;
            padding-right: 30px;
        }

        .totals-cell {
            width: 45%;
        }

        .section-label {
            font-size: 13px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 6px;
        }

        .section-text {
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }

        .notes-block {
            margin-top: 15px;
        }

        /* ── Totals ── */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px 10px;
            font-size: 13px;
        }

        .totals-table .label-col {
            text-align: left;
            color: #333;
        }

        .totals-table .amount-col {
            text-align: right;
            color: #1a1a2e;
        }

        .totals-table .total-row td {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
            border-top: 2px solid #1a1a2e;
            padding-top: 10px;
        }

        .separator {
            border-top: 1px solid #d0d0d8;
            margin: 20px 0;
        }
    </style>
</head>
<body>

    {{-- ── Header: Logo + Company Info (left) | Invoice Type + NCF (right) ── --}}
    <table class="header-table">
        <tr>
            @if(isset($company['logo']) && !empty($company['logo']))
                <td class="logo-cell">
                    <img src="{{ storage_path('app/public/' . $company['logo']) }}" alt="Logo">
                </td>
            @endif
            <td style="width: 55%;">
                <div class="company-name">{{ $company['company_name'] ?? 'Optiflow' }}</div>
                @if(!empty($company['address']))
                    <div class="company-detail">{{ $company['address'] }}</div>
                @endif
                @if(!empty($company['tax_id']))
                    <div class="company-detail">RNC {{ $company['tax_id'] }}</div>
                @endif
                @if(!empty($company['phone']))
                    <div class="company-detail"><strong>Telefono:</strong> {{ $company['phone'] }}</div>
                @endif
                @if(!empty($company['email']))
                    <div class="company-detail">{{ $company['email'] }}</div>
                @endif
                <div class="company-detail"><strong>Fecha de creacion:</strong> {{ $invoice->issue_date->format('d/m/Y') }}</div>
                <div class="company-detail"><strong>Vencimiento de la factura</strong> {{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}</div>
            </td>
            <td style="text-align: right;">
                @if($invoice->documentSubtype)
                    <div class="invoice-type">{{ $invoice->documentSubtype->name }}</div>
                    <div class="ncf-number">NCF {{ $invoice->document_number }}</div>
                @else
                    <div class="ncf-number">{{ $invoice->document_number }}</div>
                @endif
                @if($invoice->documentSubtype)
                    <div class="invoice-meta">
                        @if($invoice->documentSubtype->valid_until_date)
                            <strong>Vencimiento NCF:</strong> {{ $invoice->documentSubtype->valid_until_date->format('d/m/Y') }}
                        @else
                            <strong>Vencimiento NCF:</strong>
                        @endif
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <div class="separator"></div>

    {{-- ── Client + Amount Due ── --}}
    <table class="client-table">
        <tr>
            <td style="width: 60%;">
                <div class="client-label"><strong>Cliente:</strong> {{ $invoice->contact->name }}</div>
                @if($invoice->contact->identification_number)
                    <div class="client-label"><strong>{{ $invoice->contact->identification_type ?? 'RNC' }}:</strong> {{ $invoice->contact->identification_number }}</div>
                @else
                    <div class="client-label"><strong>RNC:</strong></div>
                @endif
                @if($invoice->contact->phone)
                    <div class="client-label"><strong>Telefono:</strong> {{ $invoice->contact->phone }}</div>
                @endif
                @if($invoice->contact->full_address)
                    <div class="client-label"><strong>Direccion:</strong> {{ $invoice->contact->full_address }}</div>
                @endif
            </td>
            <td style="width: 40%;">
                <div class="amount-due-label">Valor restante por pagar:</div>
                <div class="amount-due-value">RD${{ number_format($invoice->amount_due, 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- ── Items Table ── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%;">Cantidad</th>
                <th style="width: 30%;">Descripcion</th>
                <th style="width: 12%;">Unidad de medida</th>
                <th style="width: 15%;" class="text-right">Precio</th>
                <th style="width: 12%;" class="text-right">Descuento</th>
                <th style="width: 12%;" class="text-right">Impuesto</th>
                <th style="width: 11%;" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                @php
                    $itemTaxTotal = 0;
                    if ($item->taxes && $item->taxes->count()) {
                        foreach ($item->taxes as $tax) {
                            $itemTaxTotal += $tax->pivot->amount ?? 0;
                        }
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ intval($item->quantity) == $item->quantity ? intval($item->quantity) : number_format($item->quantity, 2) }}</td>
                    <td class="font-bold">{{ $item->description }}</td>
                    <td class="text-center">{{ $item->product?->unit ?? 'Unidad' }}</td>
                    <td class="text-right">RD${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">
                        @if(($item->discount_amount ?? 0) > 0)
                            RD${{ number_format($item->discount_amount, 2) }}
                        @endif
                    </td>
                    <td class="text-right">RD${{ number_format($itemTaxTotal, 2) }}</td>
                    <td class="text-right font-bold">RD${{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line-count">Total de lineas: {{ $invoice->items->count() }}</div>

    <div class="separator"></div>

    {{-- ── Bottom: Notes (left) + Totals (right) ── --}}
    @php
        $taxBreakdown = [];
        $taxableAmount = 0;

        foreach ($invoice->items as $item) {
            if ($item->taxes && $item->taxes->count()) {
                foreach ($item->taxes as $tax) {
                    $taxName = $tax->name;
                    $taxRate = $tax->pivot->rate ?? $tax->rate ?? 0;
                    $taxAmount = $tax->pivot->amount ?? 0;
                    $taxKey = $taxName . ' (' . number_format($taxRate, 2) . '%)';

                    if (!isset($taxBreakdown[$taxKey])) {
                        $taxBreakdown[$taxKey] = 0;
                    }
                    $taxBreakdown[$taxKey] += $taxAmount;
                }
                $taxableAmount += ($item->subtotal ?? ($item->quantity * $item->unit_price)) - ($item->discount_amount ?? 0);
            }
        }
    @endphp

    <table class="bottom-table">
        <tr>
            <td class="notes-cell">
                @if($invoice->notes)
                    <div class="notes-block">
                        <div class="section-label">Notas:</div>
                        <div class="section-text">{{ $invoice->notes }}</div>
                    </div>
                @elseif(!empty($company['terms_conditions']))
                    <div class="notes-block">
                        <div class="section-label">Notas:</div>
                        <div class="section-text">{{ $company['terms_conditions'] }}</div>
                    </div>
                @endif
            </td>
            <td class="totals-cell">
                <table class="totals-table">
                    <tr>
                        <td class="label-col">SUBTOTAL</td>
                        <td class="amount-col">RD${{ number_format($invoice->subtotal_amount, 2) }}</td>
                    </tr>
                    @if($taxableAmount > 0)
                        <tr>
                            <td class="label-col">MONTO GRAVADO</td>
                            <td class="amount-col">RD${{ number_format($taxableAmount, 2) }}</td>
                        </tr>
                    @endif
                    @if($invoice->discount_amount > 0)
                        <tr>
                            <td class="label-col">DESCUENTO</td>
                            <td class="amount-col">-RD${{ number_format($invoice->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    @foreach($taxBreakdown as $taxName => $taxAmount)
                        <tr>
                            <td class="label-col">{{ $taxName }}</td>
                            <td class="amount-col">RD${{ number_format($taxAmount, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td class="label-col">Total</td>
                        <td class="amount-col">RD${{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
