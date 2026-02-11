<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja - {{ $date->format('d/m/Y') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #1a1a2e;
            padding: 30px 35px 25px;
        }

        /* ── Header ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 100px;
            text-align: center;
            vertical-align: middle;
            padding-right: 12px;
        }

        .logo-cell img {
            max-height: 70px;
            max-width: 95px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 2px;
        }

        .company-detail {
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }

        .workspace-name {
            font-size: 12px;
            font-weight: bold;
            color: #555;
            margin-top: 4px;
        }

        .report-box {
            border: 2px solid #999;
            padding: 10px 15px;
            text-align: center;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            background-color: #1a1a2e;
            padding: 6px 10px;
            margin: -10px -15px 8px;
        }

        .report-date {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
        }

        .report-workspace {
            font-size: 11px;
            color: #555;
            margin-top: 4px;
        }

        .separator {
            border-top: 1px solid #d0d0d8;
            margin: 15px 0;
        }

        /* ── Section titles ── */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #fff;
            background-color: #1a1a2e;
            padding: 7px 10px;
            margin-bottom: 0;
        }

        /* ── Summary tables ── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .summary-table thead th {
            background-color: #f0f0f5;
            color: #1a1a2e;
            font-weight: bold;
            font-size: 11px;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 2px solid #d0d0d8;
        }

        .summary-table tbody td {
            padding: 7px 10px;
            font-size: 12px;
            border-bottom: 1px solid #e8e8ec;
            vertical-align: middle;
        }

        .summary-table tbody tr:last-child td {
            border-bottom: 2px solid #d0d0d8;
        }

        .summary-table tfoot td {
            padding: 8px 10px;
            font-size: 13px;
            font-weight: bold;
            color: #1a1a2e;
            background-color: #f0f0f5;
            border-top: 2px solid #1a1a2e;
        }

        /* ── Detail table ── */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .detail-table thead th {
            background-color: #f0f0f5;
            color: #1a1a2e;
            font-weight: bold;
            font-size: 10px;
            padding: 6px 6px;
            text-align: left;
            border-bottom: 2px solid #d0d0d8;
        }

        .detail-table tbody td {
            padding: 5px 6px;
            font-size: 10px;
            border-bottom: 1px solid #e8e8ec;
            vertical-align: middle;
        }

        .detail-table tbody tr:nth-child(even) {
            background-color: #fafafa;
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

        .text-warning {
            color: #b45309;
        }

        .text-success {
            color: #15803d;
        }

        /* ── Grand total box ── */
        .grand-total-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .grand-total-table td {
            padding: 12px 15px;
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
        }

        .grand-total-table .total-row td {
            background-color: #1a1a2e;
            color: #fff;
            font-size: 16px;
        }

        /* ── Billing summary box ── */
        .billing-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .billing-summary-table td {
            padding: 10px 15px;
            font-size: 13px;
            border-bottom: 1px solid #e8e8ec;
        }

        .billing-summary-table tr:last-child td {
            border-bottom: none;
        }

        .billing-summary-table .label-cell {
            font-weight: bold;
            color: #1a1a2e;
        }

        .billing-summary-table .value-cell {
            text-align: right;
            font-weight: bold;
        }

        .billing-summary-table .pending-row td {
            background-color: #fef3c7;
            color: #92400e;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #d0d0d8;
            padding-top: 10px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            font-size: 10px;
            color: #666;
            vertical-align: middle;
        }

        .no-data {
            padding: 20px;
            text-align: center;
            color: #999;
            font-style: italic;
            font-size: 13px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>

    {{-- ── Header ── --}}
    <table class="header-table">
        <tr>
            @if(isset($company['logo']) && !empty($company['logo']))
                <td class="logo-cell">
                    <img src="{{ storage_path('app/public/' . $company['logo']) }}" alt="Logo">
                </td>
            @endif
            <td>
                <div class="company-name">{{ $company['company_name'] ?? 'Empresa' }}</div>
                @if(!empty($company['address']))
                    <div class="company-detail">{{ $company['address'] }}</div>
                @endif
                @if(!empty($company['tax_id']))
                    <div class="company-detail">RNC {{ $company['tax_id'] }}</div>
                @endif
                @if(!empty($company['phone']))
                    <div class="company-detail">{{ $company['phone'] }}</div>
                @endif
                <div class="workspace-name">Sucursal: {{ $workspaceName }}</div>
            </td>
            <td style="width: 200px; vertical-align: middle;">
                <div class="report-box">
                    <div class="report-title">CIERRE DE CAJA</div>
                    <div class="report-date">{{ $date->format('d/m/Y') }}</div>
                    <div class="report-workspace">{{ $workspaceName }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="separator"></div>

    {{-- ── Resumen de Facturación del Día ── --}}
    @if(count($invoiceSummary) > 0)
        <div class="section-title">RESUMEN DE FACTURACION DEL DÍA</div>
        <table class="billing-summary-table">
            <tr>
                <td class="label-cell">Total facturado</td>
                <td class="value-cell">RD${{ number_format($totalInvoiced, 2) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Total cobrado</td>
                <td class="value-cell text-success">RD${{ number_format($totalPaidOnInvoices, 2) }}</td>
            </tr>
            @if($totalPending > 0)
                <tr class="pending-row">
                    <td class="label-cell">Total pendiente de cobro</td>
                    <td class="value-cell">RD${{ number_format($totalPending, 2) }}</td>
                </tr>
            @endif
        </table>

        {{-- ── Detalle de Facturas del Día ── --}}
        <div class="section-title">DETALLE DE FACTURAS DEL DÍA</div>
        <table class="detail-table">
            <thead>
                <tr>
                    <th style="width: 15%;">No. Factura</th>
                    <th style="width: 25%;">Cliente</th>
                    <th style="width: 15%;">Estado</th>
                    <th style="width: 15%;" class="text-right">Total</th>
                    <th style="width: 15%;" class="text-right">Pagado</th>
                    <th style="width: 15%;" class="text-right">Pendiente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoiceSummary as $inv)
                    <tr>
                        <td>{{ $inv['document_number'] }}</td>
                        <td>{{ $inv['contact_name'] }}</td>
                        <td>{{ $inv['status'] }}</td>
                        <td class="text-right">RD${{ number_format($inv['total_amount'], 2) }}</td>
                        <td class="text-right">RD${{ number_format($inv['paid_amount'], 2) }}</td>
                        <td class="text-right font-bold {{ $inv['pending_amount'] > 0 ? 'text-warning' : 'text-success' }}">
                            RD${{ number_format($inv['pending_amount'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">TOTALES</td>
                    <td class="text-right">RD${{ number_format($totalInvoiced, 2) }}</td>
                    <td class="text-right">RD${{ number_format($totalPaidOnInvoices, 2) }}</td>
                    <td class="text-right">RD${{ number_format($totalPending, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="section-title">RESUMEN DE FACTURACION DEL DÍA</div>
        <div class="no-data">No se emitieron facturas en esta fecha.</div>
    @endif

    <div class="separator"></div>

    @if($payments->isEmpty())
        <div class="no-data">No se registraron pagos en esta fecha.</div>
    @else

        {{-- ── 1. Resumen por Método de Pago ── --}}
        <div class="section-title">RESUMEN POR METODO DE PAGO</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Metodo de Pago</th>
                    <th style="width: 20%;" class="text-center">Cantidad</th>
                    <th style="width: 30%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paymentMethodSummary as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="text-center">{{ $row['count'] }}</td>
                        <td class="text-right">RD${{ number_format($row['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>TOTAL</td>
                    <td class="text-center">{{ $payments->count() }}</td>
                    <td class="text-right">RD${{ number_format($grandTotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- ── 2. Resumen por Cuenta Bancaria ── --}}
        <div class="section-title">RESUMEN POR CUENTA BANCARIA</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Cuenta</th>
                    <th style="width: 20%;" class="text-center">Cantidad</th>
                    <th style="width: 30%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bankAccountSummary as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="text-center">{{ $row['count'] }}</td>
                        <td class="text-right">RD${{ number_format($row['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>TOTAL</td>
                    <td class="text-center">{{ $payments->count() }}</td>
                    <td class="text-right">RD${{ number_format($grandTotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- ── 3. Resumen por Cliente ── --}}
        @if(count($contactSummary) > 0)
            <div class="section-title">RESUMEN POR CLIENTE</div>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Cliente</th>
                        <th style="width: 20%;" class="text-center">Cantidad</th>
                        <th style="width: 30%;" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contactSummary as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-center">{{ $row['count'] }}</td>
                            <td class="text-right">RD${{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>TOTAL</td>
                        <td class="text-center">{{ $payments->count() }}</td>
                        <td class="text-right">RD${{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif

        {{-- ── Grand Total ── --}}
        <table class="grand-total-table">
            <tr class="total-row">
                <td>TOTAL COBRADO EN EL DÍA</td>
                <td class="text-right">RD${{ number_format($grandTotal, 2) }}</td>
            </tr>
        </table>

        {{-- ── 4. Detalle de Pagos ── --}}
        <div class="section-title">DETALLE DE PAGOS</div>
        <table class="detail-table">
            <thead>
                <tr>
                    <th style="width: 8%;">No.</th>
                    <th style="width: 20%;">Cliente</th>
                    <th style="width: 12%;">Factura</th>
                    <th style="width: 15%;">Metodo</th>
                    <th style="width: 15%;">Cuenta</th>
                    <th style="width: 15%;" class="text-right">Monto</th>
                    <th style="width: 15%;" class="text-right">Pend. Fact.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    @php
                        $contact = $payment->contact ?? $payment->invoice?->contact;
                        $invoicePending = $payment->invoice
                            ? max(0, $payment->invoice->total_amount - $payment->invoice->payments->sum('amount'))
                            : null;
                    @endphp
                    <tr>
                        <td>{{ $payment->payment_number }}</td>
                        <td>{{ $contact?->name ?? 'N/A' }}</td>
                        <td>{{ $payment->invoice?->document_number ?? '-' }}</td>
                        <td>{{ $payment->payment_method->label() }}</td>
                        <td>{{ $payment->bankAccount?->name ?? '-' }}</td>
                        <td class="text-right font-bold">RD${{ number_format($payment->amount, 2) }}</td>
                        <td class="text-right {{ $invoicePending > 0 ? 'text-warning font-bold' : '' }}">
                            @if($invoicePending !== null)
                                RD${{ number_format($invoicePending, 2) }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    @endif

    {{-- ── Footer ── --}}
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>Generado por: {{ $generatedBy }} | Sucursal: {{ $workspaceName }}</td>
                <td class="text-right">Fecha de impresion: {{ now()->format('d/m/Y h:i A') }}</td>
            </tr>
        </table>
    </div>

</body>
</html>
