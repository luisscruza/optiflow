<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receta - {{ $prescription->patient->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 25px 30px;
            padding: 0;
            font-size: 11px;
            line-height: 1.5;
            color: #1a1a1a;
            background: white;
        }

        .header-table {
            width: 100%;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2c5aa0;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .contact-info {
            font-size: 9.5px;
            margin-bottom: 3px;
            color: #000;
            line-height: 1.4;
        }

        .contact-info strong {
            color: #000;
        }

        .logo-cell {
            text-align: center;
            vertical-align: middle;
            padding: 0 15px;
        }

        .prescription-numbers {
            font-size: 10px;
            margin-bottom: 5px;
            color: #000;
        }

        .validity-info {
            font-size: 9px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #e0e0e0;
            color: #666;
        }

        .date-location {
            font-size: 9.5px;
            margin: 12px 0;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-left: 3px solid #2c5aa0;
            color: #000;
        }

        .title {
            text-align: center;
            font-size: 17px;
            font-weight: bold;
            margin: 20px 0 18px 0;
            text-transform: uppercase;
            color: #000;
            letter-spacing: 1.5px;
        }

        .patient-info {
            font-size: 10px;
            margin-bottom: 18px;
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }

        .patient-info-line {
            margin-bottom: 5px;
            line-height: 1.6;
            color: #000;
        }

        .patient-info strong {
            color: #000;
        }

        .prescription-table {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .prescription-table th {
            color: #000;
            padding: 10px 6px;
            text-align: center;
            border: 1px solid #000;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .prescription-table td {
            padding: 10px 6px;
            border: 1px solid #000;
            text-align: center;
            font-size: 10.5px;
            background-color: #ffffff;
        }

        .prescription-table tbody tr:nth-child(odd) {
            background-color: #f8f9fa;
        }

        .prescription-table .eye-label {
            text-align: left;
            padding-left: 18px;
            color: #000;
            background-color: #fff !important;
        }

        .footer-info {
            font-size: 10px;
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }

        .footer-line {
            margin-bottom: 8px;
            line-height: 1.6;
            color: #000;
        }

        .footer-line strong {
            color: #000;
        }

        .signature-section {
            margin-top: 50px;
            text-align: center;
        }

        .signature-line {
            border-top: 2px solid #2c5aa0;
            width: 280px;
            margin: 0 auto;
            padding-top: 8px;
            font-size: 10px;
            text-align: center;
            color: #000;
        }

        .section-title {
            font-size: 11px;
            color: #000;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .distances-section {
            margin: 15px 0;
            padding: 0;
            font-size: 11px;
            display: table;
            width: 100%;
        }

        .distance-item {
            display: inline-block;
            margin-right: 40px;
            padding: 8px 15px;
            background-color: #fff;
            border: 1px solid #d0d0d0;
            border-radius: 4px;
        }

        .distance-item strong {
            color: #000;
        }

    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div class="company-name">
                    {{ $company['company_name'] }}
                </div>
                <div class="contact-info">
                     {{ $prescription->workspace->address }} 
                </div>
                     <div class="contact-info">
                     {{ $prescription->workspace->phone }} 
                </div>

            </td>
            <td class="logo-cell" style="width: 22%; vertical-align: middle;">
                @if(isset($company['logo']) && !empty($company['logo']))
                    <img src="{{ storage_path('app/public/' . $company['logo']) }}" alt="Logo" style="max-height: 90px; max-width: 160px; display: block; margin: 0 auto;">
                @endif
            </td>
            <td style="width: 30%; vertical-align: top; text-align: right;">
                <div class="prescription-numbers">
                    HISTORIA N° {{ str_pad($prescription->patient->id, 5, '0', STR_PAD_LEFT) }}
                </div>
                <div class="prescription-numbers">
                    RECETA N° {{ str_pad($prescription->id, 5, '0', STR_PAD_LEFT) }}
                </div>
                <div class="validity-info">
                    <strong>Vigencia:</strong> 2 meses<br>
                    <strong>Sucursal:</strong> {{ $prescription->workspace->name }} <br>
                                        <strong>Evaluado por:</strong> {{ $prescription->optometrist->name }} <br>

                    <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($prescription->created_at)->locale('es')->isoFormat('D [de] MMMM [de] YYYY h:mm A') }}
                </div>
            </td>
        </tr>
    </table>
    <!-- Title -->
    <div class="title">
        Prescripción de Lentes
    </div>

    <!-- Patient info -->
    <div class="patient-info">
        <div class="patient-info-line">
            <strong>Paciente:</strong> {{ strtoupper($prescription->patient->name) }}
            @if($prescription->patient->identification_number)
            <strong>Identificación:</strong> {{ $prescription->patient->identification_number }}
            @endif
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            @if($prescription->patient->phone_primary)
            <strong>Teléfono:</strong> {{ $prescription->patient->phone_primary }}
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            @endif
            <strong>Edad:</strong> {{ $prescription->patient->age }} año(s)
        </div>
    </div>

    <!-- Prescription table -->
    <table class="prescription-table">
        <thead>
            <tr>
                <th style="width: 16%;">Rx Final</th>
                <th style="width: 13%;">Esfera</th>
                <th style="width: 13%;">Cilindro</th>
                <th style="width: 10%;">Eje</th>
                <th style="width: 13%;">Adición</th>
                <th style="width: 10%;">Alt Bif</th>
                <th style="width: 12%;">AV Lejos</th>
                <th style="width: 13%;">AV Cerca</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="eye-label">OD (Derecho)</td>
                <td><strong>{{ $prescription->subjetivo_od_esfera ?? '-' }}</strong></td>
                <td><strong>{{ $prescription->subjetivo_od_cilindro ?? '-' }}</strong></td>
                <td><strong>{{ $prescription->subjetivo_od_eje ?? '-' }}</strong></td>
                <td><strong>{{ $prescription->subjetivo_od_add ?? '-' }}</strong></td>
                <td><strong>{{ $prescription->alt_bif_od ?? '-' }}</strong></td>
                <td>{{ $prescription->subjetivo_od_av_lejos ? '20/' . $prescription->subjetivo_od_av_lejos : '-' }}</td>
                <td>{{ $prescription->subjetivo_od_av_cerca ? '20/' . $prescription->subjetivo_od_av_cerca : '-' }}</td>
            </tr>
            <tr>
                <td class="eye-label">OI (Izquierdo)</td>
                <td><strong>{{ $prescription->subjetivo_oi_esfera ?? '-' }}</strong></td>
                <td><strong>{{ $prescription->subjetivo_oi_cilindro ?? '-' }}</strong></td>
                <td><strong>{{ $prescription->subjetivo_oi_eje ?? '-' }}</strong></td>
                <td><strong>{{ $prescription->subjetivo_oi_add ?? '-' }}</strong></td>
                <td><strong>{{ $prescription->alt_bif_oi ?? '-' }}</strong></td>
                <td>{{ $prescription->subjetivo_oi_av_lejos ? '20/' . $prescription->subjetivo_oi_av_lejos : '-' }}</td>
                <td>{{ $prescription->subjetivo_oi_av_cerca ? '20/' . $prescription->subjetivo_oi_av_cerca : '-' }}</td>
            </tr>
        </tbody>
    </table>

    @php
        $dpOd = $prescription->subjetivo_od_dp ?? null;
        $dpOi = $prescription->subjetivo_oi_dp ?? null;
        
        // Determinar si mostrar DP junto o separado
        $showCombinedDP = $dpOd && $dpOi && $dpOd === $dpOi;
        $showSeparateDP = ($dpOd && $dpOi && $dpOd !== $dpOi) || ($dpOd && !$dpOi) || (!$dpOd && $dpOi);
    @endphp

    @if($showCombinedDP || $showSeparateDP)
    <!-- Distances section -->
    <div class="distances-section">
        @if($showCombinedDP)
            <div class="distance-item">
                <strong>Distancia Naso Pupilar:</strong> {{ $dpOd }}mm
            </div>
        @else
            @if($dpOd)
            <div class="distance-item">
                <strong>DNP OD:</strong> {{ $dpOd }}mm
            </div>
            @endif
            @if($dpOi)
            <div class="distance-item">
                <strong>DNP OI:</strong> {{ $dpOi }}mm
            </div>
            @endif
        @endif
    </div>
    @endif

    <!-- Footer information -->
    <div class="footer-info">
        <div class="footer-line">
            <strong>Próximo Control Visual:</strong> {{ \Carbon\Carbon::parse($prescription->created_at)->addMonths(12)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
        </div>
        @if($prescription->motivos && $prescription->motivos->count() > 0)
            <div class="footer-line">
                <strong>Diagnósticos:</strong>
                @foreach($prescription->motivos as $motivo)
                    {{ ucfirst($motivo->name) }}{{ !$loop->last ? ', ' : '' }}
                @endforeach
            </div>
        @endif
        @if($prescription->lentesRecomendados && $prescription->lentesRecomendados->count() > 0)
            <div class="footer-line">
                <strong>Tipo de Lentes Recomendados:</strong>
                @foreach($prescription->lentesRecomendados as $lente)
                    {{ ucfirst($lente->name) }}{{ !$loop->last ? ', ' : '' }}
                @endforeach
            </div>
        @endif
        @if($prescription->refraccion_observaciones)
            <div class="footer-line">
                <strong>Observaciones Adicionales:</strong> {{ $prescription->refraccion_observaciones }}
            </div>
        @endif
    </div>

    <!-- Signature section -->
    <div class="signature-section">
        <div class="signature-line">
            FIRMA PROFESIONAL
        </div>
    </div>
</body>
</html>
