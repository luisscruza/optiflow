<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo {{ $payment->payment_number }}</title>
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
            margin-bottom: 25px;
        }

        .header-table td {
            vertical-align: top;
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

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a2e;
            text-align: center;
            margin-bottom: 2px;
        }

        .company-detail {
            font-size: 12px;
            color: #333;
            text-align: center;
            line-height: 1.5;
        }

        .receipt-box {
            border: 2px solid #999;
            padding: 10px 15px;
            text-align: center;
        }

        .receipt-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a2e;
            background-color: #d0d0d8;
            padding: 6px 10px;
            margin: -10px -15px 8px;
        }

        .receipt-number {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
        }

        /* ── Client info section ── */
        .client-section {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .client-section td {
            padding: 5px 8px;
            font-size: 12px;
            vertical-align: middle;
        }

        .client-label {
            background-color: #d0d0d8;
            font-weight: bold;
            font-size: 11px;
            color: #1a1a2e;
            width: 100px;
            text-align: right;
            padding-right: 10px;
        }

        .client-value {
            font-size: 12px;
            color: #1a1a2e;
            border-bottom: 1px solid #ccc;
        }

        .payment-info-label {
            background-color: #d0d0d8;
            font-weight: bold;
            font-size: 11px;
            color: #1a1a2e;
            text-align: right;
            padding-right: 10px;
            width: 120px;
        }

        .payment-info-value {
            font-size: 12px;
            color: #1a1a2e;
        }

        .date-header {
            background-color: #d0d0d8;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            padding: 6px;
        }

        .date-value {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 8px;
        }

        /* ── Items table ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .items-table thead th {
            background-color: #d0d0d8;
            color: #1a1a2e;
            font-weight: bold;
            font-size: 12px;
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #999;
        }

        .items-table tbody td {
            padding: 12px 8px;
            font-size: 12px;
            border-left: 1px solid #999;
            border-right: 1px solid #999;
            vertical-align: top;
            min-height: 200px;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 1px solid #999;
        }

        .items-body-row td {
            height: 200px;
            vertical-align: top;
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

        /* ── Totals ── */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .totals-table td {
            padding: 6px 10px;
            font-size: 13px;
        }

        .totals-table .label-col {
            text-align: right;
            color: #333;
            font-weight: bold;
        }

        .totals-table .amount-col {
            text-align: right;
            color: #1a1a2e;
            width: 160px;
        }

        .totals-table .total-row td {
            background-color: #d0d0d8;
            font-size: 14px;
            font-weight: bold;
            color: #1a1a2e;
        }

        /* ── Signatures ── */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 60px;
        }

        .signatures-table td {
            width: 50%;
            vertical-align: bottom;
            padding: 0 20px;
        }

        .signature-line {
            border-top: 1px solid #333;
            padding-top: 8px;
            font-size: 11px;
            font-weight: bold;
            color: #1a1a2e;
            text-align: center;
        }
    </style>
</head>
<body>

    @php
        $contact = null;

        if ($payment->isInvoicePayment() && $payment->invoice) {
            $contact = $payment->invoice->contact;
        } elseif ($payment->contact) {
            $contact = $payment->contact;
        }

        $paymentMethodLabel = match ($payment->payment_method->value) {
            'cash' => 'Efectivo',
            'check' => 'Cheque',
            'credit_card' => 'Tarjeta de crédito/débito',
            'bank_transfer' => 'Transferencia bancaria',
            'mobile_payment' => 'Pago móvil',
            'transfer' => 'Transferencia',
            'other' => 'Otro',
            default => ucfirst($payment->payment_method->value),
        };
    @endphp

    {{-- ── Header: Logo + Company Info (center) | Receipt Box (right) ── --}}
    <table class="header-table">
        <tr>
            @if(isset($company['logo']) && !empty($company['logo']))
                <td class="logo-cell">
                    <img src="{{ storage_path('app/public/' . $company['logo']) }}" alt="Logo">
                </td>
            @endif
            <td style="text-align: center;">
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
                @if(!empty($company['email']))
                    <div class="company-detail">{{ $company['email'] }}</div>
                @endif
            </td>
            <td style="width: 180px; vertical-align: middle;">
                <div class="receipt-box">
                    <div class="receipt-title">RECIBO DE CAJA</div>
                    <div class="receipt-number">No. {{ $payment->id }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Client Info + Payment Method + Date ── --}}
    <table class="client-section">
        <tr>
            <td class="client-label">SEÑOR(ES)</td>
            <td class="client-value" colspan="3">{{ $contact?->name ?? 'N/A' }}</td>
            <td class="date-header" rowspan="2" style="width: 140px;">FECHA</td>
        </tr>
        <tr>
            <td class="client-label">DIRECCIÓN</td>
            <td class="client-value" colspan="3">{{ $contact?->full_address ?? '' }}</td>
        </tr>
        <tr>
            <td class="client-label">CIUDAD</td>
            <td class="client-value">{{ $contact?->primaryAddress?->city ?? '' }}</td>
            <td colspan="2"></td>
            <td class="date-value" rowspan="3">{{ $payment->payment_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="client-label">TELÉFONO</td>
            <td class="client-value">{{ $contact?->phone_primary ?? $contact?->mobile ?? '' }}</td>
            <td class="payment-info-label">FORMA DE PAGO</td>
            <td class="payment-info-value">{{ $paymentMethodLabel }}</td>
        </tr>
        <tr>
            <td class="client-label">RNC</td>
            <td class="client-value">{{ $contact?->identification_number ?? '' }}</td>
            <td class="payment-info-label">CUENTA</td>
            <td class="payment-info-value">{{ $payment->bankAccount?->name ?? '' }}</td>
        </tr>
    </table>

    {{-- ── Items / Concept Table ── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 60%;">CONCEPTO</th>
                <th style="width: 20%;" class="text-right">IMPUESTO</th>
                <th style="width: 20%;" class="text-right">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr class="items-body-row">
                <td>
                    @if($payment->isInvoicePayment() && $payment->invoice)
                        Pago a factura No. {{ $payment->invoice->document_number }}
                    @elseif($payment->isOtherIncome() && $payment->lines && $payment->lines->count() > 0)
                        @foreach($payment->lines as $line)
                            <div style="margin-bottom: 4px;">{{ $line->description }}</div>
                        @endforeach
                    @else
                        {{ $payment->note ?? 'Pago recibido' }}
                    @endif
                </td>
                <td class="text-right">
                    @if($payment->tax_amount > 0)
                        RD${{ number_format($payment->tax_amount, 2) }}
                    @endif
                </td>
                <td class="text-right">RD${{ number_format($payment->isOtherIncome() ? $payment->subtotal_amount : $payment->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ── Totals ── --}}
    <table class="totals-table">
        <tr>
            <td class="label-col">Subtotal</td>
            <td class="amount-col">RD${{ number_format($payment->isOtherIncome() ? $payment->subtotal_amount : $payment->amount, 2) }}</td>
        </tr>
        @if($payment->tax_amount > 0)
            <tr>
                <td class="label-col">Impuestos</td>
                <td class="amount-col">RD${{ number_format($payment->tax_amount, 2) }}</td>
            </tr>
        @endif
        @if($payment->withholding_amount > 0)
            <tr>
                <td class="label-col">Retenciones</td>
                <td class="amount-col">-RD${{ number_format($payment->withholding_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td class="label-col">Total</td>
            <td class="amount-col">RD${{ number_format($payment->amount, 2) }}</td>
        </tr>
    </table>

    {{-- ── Note ── --}}
    @if($payment->note)
        <div style="margin-bottom: 20px; font-size: 12px; color: #333;">
            <strong>Nota:</strong> {{ $payment->note }}
        </div>
    @endif

    {{-- ── Signatures ── --}}
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line">ELABORADO POR</div>
            </td>
            <td>
                <div class="signature-line">ACEPTADA, FIRMA Y/O SELLO Y FECHA</div>
            </td>
        </tr>
    </table>

</body>
</html>
