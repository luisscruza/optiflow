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
            margin: 20px;
            padding: 0;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            background: white;
        }

        .header {
            width: 100%;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
            overflow: hidden;
        }

        .header-left {
            float: left;
            width: 65%;
        }

        .header-right {
            float: right;
            width: 33%;
            text-align: right;
        }

        .doctor-info {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .contact-info {
            font-size: 10px;
            margin-bottom: 2px;
        }

        .prescription-numbers {
            font-size: 11px;
            font-weight: bold;
        }

        .date-location {
            font-size: 10px;
            margin: 10px 0;
            text-align: left;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            text-transform: uppercase;
        }

        .patient-info {
            font-size: 10px;
            margin-bottom: 15px;
        }

        .patient-info-line {
            margin-bottom: 3px;
        }

        .prescription-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin: 15px 0;
        }

        .prescription-table th {
            background-color: #fff;
            color: #000;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #000;
            font-size: 10px;
        }

        .prescription-table td {
            padding: 6px 4px;
            border: 1px solid #000;
            text-align: center;
            font-size: 10px;
        }

        .prescription-table .eye-label {
            text-align: left;
            padding-left: 15px;
            font-weight: normal;
        }

        .footer-info {
            font-size: 10px;
            margin-top: 15px;
        }

        .footer-line {
            margin-bottom: 5px;
        }

        .signature-section {
            margin-top: 40px;
            text-align: right;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 300px;
            margin-left: auto;
            padding-top: 5px;
            font-size: 10px;
            text-align: center;
        }

    </style>
</head>
<body>
    <!-- Header -->
    <table style="width: 100%; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px;">
        <tr>
            <td style="width: 65%; vertical-align: top;">
                <div class="doctor-info">
                    {{ $company['company_name'] }}
                </div>
                <div class="contact-info">
                    @if(isset($company['phone']) && !empty($company['phone']))
                    Teléfono: {{ $company['phone'] ?? $prescription->workspace->phone ?? '-' }}
                    @endif
                </div>
                <div class="contact-info">
                    {{ $company['company_email'] ?? '' }}
                </div>
                <div class="contact-info">
                    {{ $company['company_address'] ?? $prescription->workspace->address }} 
                </div>
            </td>
            <td style="width: 35%; vertical-align: top; text-align: right;">
                <div class="prescription-numbers">
                    Historia N° &nbsp;&nbsp; {{ str_pad($prescription->patient->id, 5, '0', STR_PAD_LEFT) }}
                </div>
                <div class="prescription-numbers">
                    Receta N° &nbsp;&nbsp; {{ str_pad($prescription->id, 5, '0', STR_PAD_LEFT) }}
                </div>
                <div class="contact-info" style="margin-top: 5px;">
                    Vigencia: (2) DOS MESES
                </div>
                <div class="contact-info">
                    Sucursal: {{ $prescription->workspace->name }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Date and location -->
    <div class="date-location">
        {{ ucfirst(\Carbon\Carbon::parse($prescription->created_at)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY [a las] h:mm:ss a')) }}
    </div>

    <hr style="border: none; border-top: 1px solid #000; margin: 10px 0;">

    <!-- Title -->
    <div class="title">
        PRESCRIPCIÓN DE LENTES
    </div>

    <!-- Patient info -->
    <div class="patient-info">
        <div class="patient-info-line">
            <strong>Paciente:</strong> {{ strtoupper($prescription->patient->name) }}
            &nbsp;&nbsp;&nbsp;&nbsp;
            <strong>Doc:</strong> {{ $prescription->patient->identification_number ?? 'N/A' }}
            &nbsp;&nbsp;&nbsp;&nbsp;
            <strong>Celular:</strong> {{ $prescription->patient->phone_primary ?? 'N/A' }}
            &nbsp;&nbsp;&nbsp;&nbsp;
            <strong>Edad:</strong> {{ $prescription->patient->age }} años
        </div>
        <div class="patient-info-line">
            <strong>Dirección:</strong> {{ $prescription->patient->address ?? 'N/A' }}
            &nbsp;&nbsp;&nbsp;&nbsp;
            <strong>Salud:</strong> PARTICULAR
            &nbsp;&nbsp;&nbsp;&nbsp;
            <strong>Correo:</strong> {{ $prescription->patient->email ?? 'N/A' }}
        </div>
    </div>

    <!-- Prescription table -->
    <table class="prescription-table">
        <thead>
            <tr>
                <th style="width: 15%;">Rx Final</th>
                <th style="width: 11%;">Esfera</th>
                <th style="width: 11%;">Cilindro</th>
                <th style="width: 9%;">Eje</th>
                <th style="width: 11%;">Adición</th>
                <th style="width: 9%;">Alt Bif</th>
                <th style="width: 11%;">Dist. P.</th>
                <th style="width: 11%;">AV L</th>
                <th style="width: 12%;">AV C</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="eye-label">Ojo derecho</td>
                <td>{{ $prescription->subjetivo_od_esfera ?? '' }}</td>
                <td>{{ $prescription->subjetivo_od_cilindro ?? '' }}</td>
                <td>{{ $prescription->subjetivo_od_eje ?? '' }}</td>
                <td>{{ $prescription->subjetivo_od_add ?? '' }}</td>
                <td></td>
                @php
                    $dpOd = $prescription->subjetivo_od_dp ?? '';
                    $dpOi = $prescription->subjetivo_oi_dp ?? '';
                    
                    // Si ambos valores son iguales y no están vacíos, mostrar como DP junto
                    if ($dpOd && $dpOi && $dpOd === $dpOi) {
                        $dpDisplay = $dpOd;
                        $dpDisplayOi = '';
                    } else {
                        // Si son diferentes, mostrar separados
                        $dpDisplay = $dpOd;
                        $dpDisplayOi = $dpOi;
                    }
                @endphp
                <td>{{ $dpDisplay }}</td>
                <td>{{ $prescription->subjetivo_od_av_lejos ? '20/' . $prescription->subjetivo_od_av_lejos : '' }}</td>
                <td>{{ $prescription->subjetivo_od_av_cerca ? '20/' . $prescription->subjetivo_od_av_cerca : '' }}</td>
            </tr>
            <tr>
                <td class="eye-label">Ojo izquierdo</td>
                <td>{{ $prescription->subjetivo_oi_esfera ?? '' }}</td>
                <td>{{ $prescription->subjetivo_oi_cilindro ?? '' }}</td>
                <td>{{ $prescription->subjetivo_oi_eje ?? '' }}</td>
                <td>{{ $prescription->subjetivo_oi_add ?? '' }}</td>
                <td></td>
                <td>{{ $dpDisplayOi }}</td>
                <td>{{ $prescription->subjetivo_oi_av_lejos ? '20/' . $prescription->subjetivo_oi_av_lejos : '' }}</td>
                <td>{{ $prescription->subjetivo_oi_av_cerca ? '20/' . $prescription->subjetivo_oi_av_cerca : '' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Footer information -->
    <div class="footer-info">
        <div class="footer-line">
            <strong>Próximo control visual:</strong> {{ \Carbon\Carbon::parse($prescription->created_at)->addMonths(12)->format('d/m/Y') }}
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        </div>
        @if($prescription->motivos && $prescription->motivos->count() > 0)
            <div class="footer-line">
                <strong>Diagnósticos:</strong>
                @foreach($prescription->motivos as $motivo)
                    {{ strtoupper($motivo->name) }}{{ !$loop->last ? ',' : '' }}
                @endforeach
            </div>
        @endif
        @if($prescription->lentesRecomendados && $prescription->lentesRecomendados->count() > 0)
            <div class="footer-line">
                <strong>Recomendación:</strong>
                @foreach($prescription->lentesRecomendados as $lente)
                    {{ strtoupper($lente->name) }}{{ !$loop->last ? ',' : '' }}
                @endforeach
            </div>
        @endif
        @if($prescription->refraccion_observaciones)
            <div class="footer-line">
                <strong>Observaciones:</strong> {{ $prescription->refraccion_observaciones }}
            </div>
        @endif
    </div>

    <!-- Signature section -->
    <div class="signature-section">
        <div class="signature-line">
            Firma profesional
        </div>
    </div>
</body>
</html>
