<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proceso de {{ $job->contact?->name ?? $job->prescription?->patient?->name ?? 'N/A' }} - {{ $company['company_name'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 20px 25px;
            padding: 0;
            font-size: 11px;
            line-height: 1.4;
            color: #1a1a1a;
            background: white;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .header-table td {
            padding: 4px 8px;
            font-size: 11px;
            vertical-align: top;
        }

        .header-label {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .header-value {
            text-align: center;
            font-size: 11px;
        }

        .logo-cell {
            text-align: right;
            vertical-align: middle;
        }

        /* Phone box */
        .phone-box {
            border: 2px solid #000;
            padding: 8px 12px;
            margin-bottom: 10px;
            display: inline-block;
            font-size: 13px;
            font-weight: bold;
        }

        /* Main content layout */
        .content-table {
            width: 100%;
            border-collapse: collapse;
        }

        .content-table > tbody > tr > td {
            vertical-align: top;
        }

        /* Left column - prescription */
        .prescription-box {
            border: 2px solid #000;
            padding: 15px;
            margin-right: 10px;
        }

        .section-title {
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        /* Prescription table */
        .rx-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .rx-table th,
        .rx-table td {
            border: 1px solid #000;
            padding: 6px 10px;
            text-align: center;
            font-size: 11px;
        }

        .rx-table th {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        .rx-table td.eye-label {
            text-align: left;
            font-weight: normal;
        }

        /* Distances */
        .distances {
            margin-bottom: 15px;
            font-size: 11px;
        }

        .distances-table {
            width: 100%;
            border-collapse: collapse;
        }

        .distances-table td {
            padding: 4px 0;
            text-align: center;
        }

        .distances-table .label {
            font-weight: bold;
            font-size: 11px;
        }

        .distances-table .value {
            font-size: 12px;
        }

        /* Lens properties */
        .lens-properties {
            margin-bottom: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .lens-type-box {
            display: inline-block;
            border: 1px solid #000;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: normal;
        }

        /* Priority / Due date box */
        .priority-box {
            border: 2px solid #000;
            padding: 10px 15px;
            font-size: 12px;
            margin-top: 10px;
        }

        .priority-line {
            margin-bottom: 5px;
        }

        /* Right column - frame properties */
        .frame-box {
            border: 2px solid #000;
            padding: 15px;
            font-size: 13px;
            line-height: 2.2;
        }

        .frame-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .frame-field {
            margin-bottom: 5px;
        }

        .frame-field-label {
            font-weight: bold;
        }

        .frame-field-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 120px;
        }

        .priority-label-text {
            text-transform: capitalize;
        }
    </style>
</head>
<body>

    <!-- Header Row -->
    <table class="header-table">
        <tr>
            <td style="width: 12%;">
                <div class="header-label">Fecha</div>
                <div class="header-value">{{ \Carbon\Carbon::parse($job->created_at)->format('d-m-Y') }}</div>
            </td>
            <td style="width: 22%;">
                <div class="header-label">Cliente</div>
                <div class="header-value">{{ $job->contact?->name ?? $prescription->patient?->name ?? 'N/A' }}</div>
            </td>
            <td style="width: 20%;">
                <div class="header-label">Opt&oacute;metra</div>
                <div class="header-value">{{ $prescription->optometrist?->name ? 'Opt. ' . $prescription->optometrist->name : 'N/A' }}</div>
            </td>
            <td style="width: 20%;">
                <div class="header-label">Sucursal</div>
                <div class="header-value">{{ $job->workspace?->name ?? $prescription->workspace?->name ?? 'N/A' }}</div>
            </td>
            <td style="width: 12%;">
                <div class="header-label">Proceso</div>
                <div class="header-value">{{ $prescription->id }}</div>
            </td>
            <td class="logo-cell" style="width: 14%;">
                @if(isset($company['logo']) && !empty($company['logo']))
                    <img src="{{ storage_path('app/public/' . $company['logo']) }}" alt="Logo" style="max-height: 50px; max-width: 100px;">
                @endif
            </td>
        </tr>
    </table>

    <!-- Phone -->
    <div style="text-align: right; margin-bottom: 10px;">
        <div class="phone-box">
            Tel&eacute;fono: {{ $job->contact?->phone_primary ?? $prescription->patient?->phone_primary ?? '________' }}
        </div>
    </div>

    <!-- Main Content: Two columns -->
    <table class="content-table">
        <tr>
            <!-- Left Column: Prescription Properties -->
            <td style="width: 62%; padding-right: 10px;">
                <div class="prescription-box">
                    <div class="section-title">Propiedades de la receta:</div>

                    <!-- Rx Table -->
                    <table class="rx-table">
                        <thead>
                            <tr>
                                <th style="width: 22%;">OJO</th>
                                <th style="width: 17%;">Esfera</th>
                                <th style="width: 17%;">Cilindro</th>
                                <th style="width: 12%;">Eje</th>
                                <th style="width: 16%;">Adici&oacute;n</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="eye-label">Ojo derecho</td>
                                <td>{{ $prescription->subjetivo_od_esfera ?? 'N/A' }}</td>
                                <td>{{ $prescription->subjetivo_od_cilindro ?? 'N/A' }}</td>
                                <td>{{ $prescription->subjetivo_od_eje ? $prescription->subjetivo_od_eje . '°' : 'N/A' }}</td>
                                <td>{{ $prescription->subjetivo_od_add ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="eye-label">Ojo izquierdo</td>
                                <td>{{ $prescription->subjetivo_oi_esfera ?? 'N/A' }}</td>
                                <td>{{ $prescription->subjetivo_oi_cilindro ?? 'N/A' }}</td>
                                <td>{{ $prescription->subjetivo_oi_eje ? $prescription->subjetivo_oi_eje . '°' : 'N/A' }}</td>
                                <td>{{ $prescription->subjetivo_oi_add ?? 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Distances -->
                    @php
                        $dpOd = $prescription->subjetivo_od_dp ?? null;
                        $dpOi = $prescription->subjetivo_oi_dp ?? null;
                        $dpTotal = ($dpOd && $dpOi) ? ($dpOd + $dpOi) . 'mm' : 'N/A';
                        $dnp = ($dpOd && $dpOi) ? $dpOd . '/' . $dpOi : ($dpOd ?? $dpOi ?? 'N/A');
                        $altBifOd = $prescription->alt_bif_od ?? null;
                        $altBifOi = $prescription->alt_bif_oi ?? null;
                        $altura = ($altBifOd || $altBifOi) ? ($altBifOd ?? 'N/A') . '/' . ($altBifOi ?? 'N/A') : 'N/A';
                    @endphp

                    <table class="distances-table">
                        <tr>
                            <td>
                                <div class="label">Distancia Pupilar AO:</div>
                                <div class="value">{{ $dpTotal }}</div>
                            </td>
                            <td>
                                <div class="label">Distancia Pupilar:</div>
                                <div class="value">{{ $dnp }}</div>
                            </td>
                            <td>
                                <div class="label">Altura:</div>
                                <div class="value">{{ $altura }}</div>
                            </td>
                        </tr>
                    </table>

                    <!-- Lens Properties -->
                    <div style="margin-top: 15px;">
                        <span class="lens-properties">Propiedades del lente a realizar:</span>
                        @if($job->fields)
                            <span class="lens-type-box">
                                {{ $job->fields['cristal_a_realizar']['value'] ?? '-' }}
                            </span>
                        @endif
                    </div>

                    <!-- Priority and Due Date -->
                    <div class="priority-box">
                        <div class="priority-line">
                            <strong>Prioridad:</strong>
                            <span class="priority-label-text">
                                @php
                                    $priorityLabels = [
                                        'low' => 'Baja',
                                        'medium' => 'Media',
                                        'high' => 'Alta',
                                        'urgent' => 'Urgente',
                                    ];
                                @endphp
                                {{ $job->priority ? ($priorityLabels[$job->priority] ?? $job->priority) : '________' }}
                            </span>
                        </div>
                        <div class="priority-line">
                            <strong>Fecha Vencimiento:</strong>
                            {{ $job->due_date ? \Carbon\Carbon::parse($job->due_date)->format('d-m-Y') : '________' }}
                        </div>
                    </div>
                </div>
            </td>

            <!-- Right Column: Frame Properties -->
            <td style="width: 38%;">
                <div class="frame-box">
                    <div class="frame-title">Propiedades de la montura:</div>
                    <div class="frame-field">
                        <span class="frame-field-label">Marca y Modelo:</span>
                        <span class="frame-field-line">
                            @if($job->fields)
                                {{ $job->fields['marca_y_modelo']['value'] ?? '' }}
                            @endif
                            </span>
                    </div>

                    <div class="frame-field">
                        <span class="frame-field-label">Dimensiones de la montura:</span>
                        <span class="frame-field-line">
                            @if($job->fields)
                                {{ $job->fields['dimensiones_de_montura']['value'] ?? '' }}
                            @endif
                        </span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
