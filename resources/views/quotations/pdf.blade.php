<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotizacion {{ $quotation->document_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #1a1a2e;
            margin: 0;
            padding: 0;
        }

        .page {
            height: 148.5mm;
            padding: 7mm 16mm;
            box-sizing: border-box;
        }

        .page-break {
            page-break-after: always;
        }

        /* ── Header ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .header-table td {
            vertical-align: top;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 2px;
        }

        .company-subtitle {
            font-size: 13px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 2px;
        }

        .company-detail {
            font-size: 10px;
            color: #333;
            line-height: 1.35;
        }

        .company-detail strong {
            font-weight: bold;
        }

        .document-type {
            font-size: 12px;
            font-weight: bold;
            color: #1a1a2e;
            text-align: right;
        }

        .document-number {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
            text-align: right;
            margin: 4px 0;
        }

        .document-meta {
            font-size: 10px;
            color: #333;
            text-align: right;
            line-height: 1.35;
        }

        .page-indicator {
            font-size: 10px;
            font-weight: bold;
            color: #333;
            text-align: right;
            margin-bottom: 4px;
        }

        .logo-cell {
            width: 120px;
            text-align: center;
            vertical-align: middle;
            padding-right: 15px;
        }

        .logo-cell img {
            max-height: 64px;
            max-width: 110px;
        }

        /* ── Client section ── */
        .client-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .client-table td {
            vertical-align: top;
            padding-top: 3px;
        }

        .client-label {
            font-size: 11px;
            color: #1a1a2e;
        }

        .client-label strong {
            font-weight: bold;
        }

        .total-label {
            font-size: 12px;
            font-weight: bold;
            color: #1a1a2e;
            text-align: right;
        }

        .total-value {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a2e;
            text-align: right;
        }

        /* ── Items table ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .items-table thead th {
            background-color: #f0f0f5;
            color: #1a1a2e;
            font-weight: bold;
            font-size: 10px;
            padding: 6px 6px;
            text-align: left;
            border-bottom: 2px solid #d0d0d8;
        }

        .items-table tbody td {
            padding: 8px 6px;
            font-size: 10px;
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
            font-size: 11px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 12px;
        }

        /* ── Bottom section (notes + totals) ── */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .bottom-table > tbody > tr > td {
            vertical-align: top;
        }

        .notes-cell {
            width: 55%;
            padding-right: 20px;
        }

        .totals-cell {
            width: 45%;
        }

        .section-label {
            font-size: 11px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 6px;
        }

        .section-text {
            font-size: 10px;
            color: #333;
            line-height: 1.35;
        }

        .notes-block {
            margin-top: 10px;
        }

        /* ── Totals ── */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 4px 8px;
            font-size: 11px;
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
            font-size: 13px;
            font-weight: bold;
            color: #1a1a2e;
            border-top: 2px solid #1a1a2e;
            padding-top: 6px;
        }

        .separator {
            border-top: 1px solid #d0d0d8;
            margin: 12px 0;
        }

        .footer {
            margin-top: 12px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #d0d0d8;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    @php
        $workspace = $quotation->workspace;
        $workspaceName = $workspace?->name;
        $companyAddress = $workspace?->address ?? ($company['address'] ?? null);
        $companyPhone = $workspace?->phone ?? ($company['phone'] ?? null);
        $firstPageItems = 4;
        $middlePageItems = 6;
        $lastPageItems = 6;
        $allItems = $quotation->items->values();
        $chunks = [];

        if ($allItems->count() <= $firstPageItems) {
            $chunks[] = $allItems;
        } else {
            $chunks[] = $allItems->take($firstPageItems);
            $remaining = $allItems->slice($firstPageItems)->values();

            while ($remaining->count() > $lastPageItems) {
                $chunks[] = $remaining->take($middlePageItems);
                $remaining = $remaining->slice($middlePageItems)->values();
            }

            $chunks[] = $remaining;
        }
    @endphp

    @foreach($chunks as $chunkIndex => $itemsChunk)
        @php
            $isLastPage = $chunkIndex === count($chunks) - 1;
        @endphp
        <div class="page">
            <table class="header-table">
                <tr>
                    @if(isset($company['logo']) && !empty($company['logo']))
                        <td class="logo-cell">
                            <img src="{{ storage_path('app/public/' . $company['logo']) }}" alt="Logo">
                        </td>
                    @endif
                    <td style="width: 55%;">
                        <div class="company-name">{{ $company['company_name'] ?? 'Mi Empresa' }}</div>
                        @if(!empty($workspaceName))
                            <div class="company-subtitle">{{ $workspaceName }}</div>
                        @endif
                        @if(!empty($companyAddress))
                            <div class="company-detail">{{ $companyAddress }}</div>
                        @endif
                        @if(!empty($company['tax_id']))
                            <div class="company-detail">RNC {{ $company['tax_id'] }}</div>
                        @endif
                        @if(!empty($companyPhone))
                            <div class="company-detail"><strong>Telefono:</strong> {{ $companyPhone }}</div>
                        @endif
                        @if(!empty($company['email']))
                            <div class="company-detail">{{ $company['email'] }}</div>
                        @endif
                        <div class="company-detail"><strong>Fecha de creacion:</strong> {{ $quotation->issue_date->format('d/m/Y') }}</div>
                        <div class="company-detail"><strong>Valida hasta:</strong> {{ $quotation->due_date?->format('d/m/Y') ?? 'N/A' }}</div>
                    </td>
                    <td style="text-align: right;">
                        <div class="page-indicator">PAGINA {{ $chunkIndex + 1 }}/{{ count($chunks) }}</div>
                        @if($quotation->documentSubtype)
                            <div class="document-type">{{ $quotation->documentSubtype->name }}</div>
                            <div class="document-number">{{ $quotation->document_number }}</div>
                        @else
                            <div class="document-type">Cotizacion</div>
                            <div class="document-number">{{ $quotation->document_number }}</div>
                        @endif
                        @if($quotation->documentSubtype && $quotation->documentSubtype->valid_until_date)
                            <div class="document-meta">
                                <strong>Vencimiento NCF:</strong> {{ $quotation->documentSubtype->valid_until_date->format('d/m/Y') }}
                            </div>
                        @endif
                    </td>
                </tr>
            </table>

            <div class="separator"></div>

            @if($chunkIndex === 0)
                <table class="client-table">
                    <tr>
                        <td style="width: 60%;">
                            <div class="client-label"><strong>Cliente:</strong> {{ $quotation->contact->name }}</div>
                            @if($quotation->contact->identification_number)
                                <div class="client-label"><strong>{{ $quotation->contact->identification_type ?? 'RNC' }}:</strong> {{ $quotation->contact->identification_number }}</div>
                            @else
                                <div class="client-label"><strong>RNC:</strong></div>
                            @endif
                            @if($quotation->contact->phone)
                                <div class="client-label"><strong>Telefono:</strong> {{ $quotation->contact->phone }}</div>
                            @endif
                            @if($quotation->contact->full_address)
                                <div class="client-label"><strong>Direccion:</strong> {{ $quotation->contact->full_address }}</div>
                            @endif
                        </td>
                        <td style="width: 40%;">
                            <div class="total-label">Total cotizacion:</div>
                            <div class="total-value">RD${{ number_format($quotation->total_amount, 2) }}</div>
                        </td>
                    </tr>
                </table>
            @endif

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
                    @foreach($itemsChunk as $item)
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

            <div class="line-count">Lineas en esta pagina: {{ $itemsChunk->count() }} de {{ $quotation->items->count() }}</div>

            <div class="separator"></div>

            @php
                $taxBreakdown = [];
                $taxableAmount = 0;

                foreach ($quotation->items as $item) {
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
                        @if($quotation->notes)
                            <div class="notes-block">
                                <div class="section-label">Notas:</div>
                                <div class="section-text">{{ $quotation->notes }}</div>
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
                                <td class="amount-col">RD${{ number_format($quotation->subtotal_amount, 2) }}</td>
                            </tr>
                            @if($taxableAmount > 0)
                                <tr>
                                    <td class="label-col">MONTO GRAVADO</td>
                                    <td class="amount-col">RD${{ number_format($taxableAmount, 2) }}</td>
                                </tr>
                            @endif
                            @if($quotation->discount_amount > 0)
                                <tr>
                                    <td class="label-col">DESCUENTO</td>
                                    <td class="amount-col">-RD${{ number_format($quotation->discount_amount, 2) }}</td>
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
                                <td class="amount-col">RD${{ number_format($quotation->total_amount, 2) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="footer">
                <div>Esta cotizacion no representa una factura o compromiso de compra</div>
            </div>
        </div>
        @if(! $isLastPage)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>
