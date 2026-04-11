<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferencia {{ $referenceNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 5mm 14mm 22mm 14mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.3;
            color: #1a1a2e;
            margin: 0;
            padding: 0 16px;
        }

        .header-table,
        .info-table,
        .items-table,
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            margin-bottom: 14px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 100px;
            text-align: center;
            vertical-align: middle;
            padding-right: 10px;
        }

        .logo-cell img {
            max-height: 65px;
            max-width: 95px;
        }

        .company-name {
            font-size: 12px;
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

        .document-box {
            border: 2px solid #999;
            padding: 8px 12px;
            text-align: center;
        }

        .document-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a2e;
            background-color: #d0d0d8;
            padding: 4px 10px;
            margin: -8px -12px 6px;
        }

        .document-number {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
        }

        .transfer-section {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .transfer-section td {
            padding: 4px 6px;
            font-size: 12px;
            vertical-align: middle;
        }

        .section-label {
            background-color: #d0d0d8;
            font-weight: bold;
            color: #1a1a2e;
            width: 115px;
            text-align: right;
            padding-right: 10px;
        }

        .section-value {
            color: #1a1a2e;
            border-bottom: 1px solid #ccc;
        }

        .date-header {
            background-color: #d0d0d8;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            padding: 4px;
        }

        .date-value {
            font-size: 15px;
            font-weight: bold;
            text-align: center;
            padding: 5px;
        }

        .items-table {
            margin-bottom: 12px;
        }

        .items-table th {
            background-color: #d0d0d8;
            color: #1a1a2e;
            font-weight: bold;
            font-size: 12px;
            padding: 6px 6px;
            text-align: left;
            border: 1px solid #999;
        }

        .items-table td {
            padding: 8px 6px;
            font-size: 12px;
            border-left: 1px solid #999;
            border-right: 1px solid #999;
            vertical-align: top;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 1px solid #999;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .note-box {
            margin-bottom: 18px;
            padding: 8px 10px;
            min-height: 50px;
            border: 1px solid #ccc;
            font-size: 12px;
            color: #333;
        }

        .signatures-table {
            margin-top: 28px;
            page-break-inside: avoid;
        }

        .signatures-table td {
            width: 50%;
            vertical-align: bottom;
            padding: 0 16px;
        }

        .signature-line {
            border-top: 1px solid #333;
            padding-top: 6px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: #1a1a2e;
        }
    </style>
</head>
<body>
    @php
        $workspaceName = $workspace?->name;
        $companyAddress = $workspace?->address ?? ($company['address'] ?? null);
        $companyPhone = $workspace?->phone ?? ($company['phone'] ?? null);
        $quantity = is_numeric($transfer->quantity) ? (float) $transfer->quantity : 0;
    @endphp

    <table class="header-table">
        <tr>
            @if(isset($company['logo']) && !empty($company['logo']))
                <td class="logo-cell">
                    <img src="{{ storage_path('app/public/' . $company['logo']) }}" alt="Logo">
                </td>
            @endif

            <td style="text-align: center;">
                <div class="company-name">{{ $company['company_name'] ?? 'Empresa' }}</div>
                @if(!empty($workspaceName))
                    <div class="company-detail">{{ $workspaceName }}</div>
                @endif
                @if(!empty($company['tax_id']))
                    <div class="company-detail">RNC {{ $company['tax_id'] }}</div>
                @endif
                @if(!empty($companyAddress))
                    <div class="company-detail">{{ $companyAddress }}</div>
                @endif
                @if(!empty($companyPhone))
                    <div class="company-detail">{{ $companyPhone }}</div>
                @endif
            </td>

            <td style="width: 180px; vertical-align: middle;">
                <div class="document-box">
                    <div class="document-title">TRANSFERENCIA DE ALMACEN</div>
                    <div class="document-number">No. {{ $referenceNumber }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="transfer-section">
        <tr>
            <td class="section-label">SUCURSAL ORIGEN</td>
            <td class="section-value" colspan="3">{{ $transfer->fromWorkspace?->name ?? 'N/A' }}</td>
            <td class="date-header" rowspan="2" style="width: 140px;">FECHA</td>
        </tr>
        <tr>
            <td class="section-label">SUCURSAL DESTINO</td>
            <td class="section-value" colspan="3">{{ $transfer->toWorkspace?->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="section-label">ELABORADO POR</td>
            <td class="section-value">{{ $transfer->createdBy?->name ?? 'N/A' }}</td>
            <td class="section-label">REFERENCIA</td>
            <td class="section-value">{{ $referenceNumber }}</td>
            <td class="date-value" rowspan="2">{{ $transfer->created_at?->format('d/m/Y') ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="section-label">HORA</td>
            <td class="section-value">{{ $transfer->created_at?->format('H:i') ?? 'N/A' }}</td>
            <td class="section-label">OBSERVACION</td>
            <td class="section-value">{{ $transfer->note ? 'Con nota' : 'Sin nota' }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 18%;">CODIGO</th>
                <th style="width: 42%;">PRODUCTO</th>
                <th style="width: 20%;" class="text-center">UNIDAD</th>
                <th style="width: 20%;" class="text-right">CANTIDAD</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $transfer->product?->sku ?? '-' }}</td>
                <td>{{ $transfer->product?->name ?? 'Producto sin nombre' }}</td>
                <td class="text-center">{{ $transfer->product?->unit ?? 'Unidad' }}</td>
                <td class="text-right">{{ fmod($quantity, 1.0) === 0.0 ? number_format($quantity, 0) : number_format($quantity, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="note-box">
        <strong>Observaciones:</strong> {{ $transfer->note ?? 'Sin observaciones registradas.' }}
    </div>

    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line">ENTREGADO POR</div>
            </td>
            <td>
                <div class="signature-line">RECIBIDO POR, FIRMA Y FECHA</div>
            </td>
        </tr>
    </table>
</body>
</html>
