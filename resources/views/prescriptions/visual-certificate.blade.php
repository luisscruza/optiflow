<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado visual - {{ $prescription->patient->name }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9.5px;
            color: #1a1a1a;
            margin: 20px 26px;
            line-height: 1.4;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #2c5aa0;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.4px;
        }

        .company-meta {
            font-size: 9px;
            margin-top: 4px;
        }

        .logo-cell {
            text-align: center;
            vertical-align: middle;
            padding: 0 12px;
        }

        .meta-right {
            text-align: right;
            font-size: 9px;
        }

        .title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: #dfefff;
            border: 1px solid #9bb7e0;
            padding: 4px 0;
            margin: 10px 0 12px;
        }

        .info-table,
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td,
        .data-table td,
        .data-table th {
            border: 1px solid #333;
            padding: 3px 4px;
            vertical-align: top;
            font-size: 9px;
        }

        .data-table th {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            background: #f3f5f7;
        }

        .section {
            margin-top: 10px;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            border-bottom: 1px solid #333;
            margin-bottom: 4px;
            padding-bottom: 2px;
        }

        .signature {
            margin-top: 30px;
            text-align: center;
        }

        .signature-line {
            border-top: 1.5px solid #2c5aa0;
            width: 220px;
            margin: 0 auto;
            padding-top: 5px;
            font-size: 9px;
        }
    </style>
</head>
<body>
    @php
        $value = fn ($value): string => filled($value) ? (string) $value : '-';
        $listValue = function ($items): string {
            if (! $items || $items->isEmpty()) {
                return '-';
            }

            return $items->map(fn ($item) => ucfirst($item->name))->join(', ');
        };
        $listWithOtros = function ($items, $otros) use ($listValue, $value): string {
            $itemsValue = $listValue($items);
            $otrosValue = $value($otros);

            if ($itemsValue === '-' && $otrosValue === '-') {
                return '-';
            }

            if ($itemsValue === '-') {
                return $otrosValue;
            }

            if ($otrosValue === '-') {
                return $itemsValue;
            }

            return $itemsValue.', '.$otrosValue;
        };
        $formatText = fn ($text): string => filled($text) ? nl2br(e((string) $text)) : '-';
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div class="company-name">{{ $company['company_name'] ?? $prescription->workspace->name }}</div>
                <div class="company-meta">
                    {{ $company['address'] ?? $prescription->workspace->address ?? '' }}<br>
                    {{ $company['phone'] ?? $prescription->workspace->phone ?? '' }}
                    @if(! empty($company['email']))
                        <br>{{ $company['email'] }}
                    @endif
                </div>
            </td>
            <td class="logo-cell" style="width: 22%;">
                @if(isset($company['logo']) && ! empty($company['logo']))
                    <img src="{{ storage_path('app/public/' . $company['logo']) }}" alt="Logo" style="max-height: 70px; max-width: 140px;">
                @endif
            </td>
            <td style="width: 30%; vertical-align: top;" class="meta-right">
                <div><strong>Historia N°</strong> {{ str_pad($prescription->patient->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div><strong>Receta N°</strong> {{ str_pad($prescription->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div style="margin-top: 6px;">
                    <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($prescription->created_at)->locale('es')->isoFormat('D [de] MMMM [de] YYYY h:mm A') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="title">Certificado visual</div>

    <table class="info-table">
        <tr>
            <td><strong>Paciente:</strong> {{ strtoupper($prescription->patient->name) }}</td>
            <td><strong>Documento:</strong> {{ $value($prescription->patient->identification_number) }}</td>
            <td><strong>Edad:</strong> {{ $value($prescription->patient->age ?? null) }}</td>
        </tr>
        <tr>
            <td><strong>Teléfono:</strong> {{ $value($prescription->patient->phone_primary) }}</td>
            <td><strong>Celular:</strong> {{ $value($prescription->patient->mobile) }}</td>
            <td><strong>Dirección:</strong> {{ $value($prescription->patient->full_address ?? $prescription->patient->address ?? null) }}</td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Motivo de consulta</div>
        <div>{!! $formatText($listWithOtros($prescription->motivos, $prescription->motivos_consulta_otros)) !!}</div>
    </div>

    <div class="section">
        <div class="section-title">Antecedentes personales</div>
        <div>{!! $formatText($listWithOtros($prescription->estadoActual, $prescription->estado_salud_actual_otros)) !!}</div>
    </div>

    <div class="section">
        <div class="section-title">Antecedentes familiares</div>
        <div>{!! $formatText($listWithOtros($prescription->historiaOcularFamiliar, $prescription->historia_ocular_familiar_otros)) !!}</div>
    </div>

    <div class="section">
        <div class="section-title">Agudeza visual</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th></th>
                    <th colspan="3">AV Lejos</th>
                    <th colspan="3">AV Cerca</th>
                </tr>
                <tr>
                    <th></th>
                    <th>SC</th>
                    <th>CC</th>
                    <th>PH</th>
                    <th>SC</th>
                    <th>CC</th>
                    <th>PH</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>OD</strong></td>
                    <td>{{ $value($prescription->av_lejos_sc_od) }}</td>
                    <td>{{ $value($prescription->av_lejos_cc_od) }}</td>
                    <td>{{ $value($prescription->av_lejos_ph_od) }}</td>
                    <td>{{ $value($prescription->av_cerca_sc_od) }}</td>
                    <td>{{ $value($prescription->av_cerca_cc_od) }}</td>
                    <td>{{ $value($prescription->av_cerca_ph_od) }}</td>
                </tr>
                <tr>
                    <td><strong>OI</strong></td>
                    <td>{{ $value($prescription->av_lejos_sc_oi) }}</td>
                    <td>{{ $value($prescription->av_lejos_cc_oi) }}</td>
                    <td>{{ $value($prescription->av_lejos_ph_oi) }}</td>
                    <td>{{ $value($prescription->av_cerca_sc_oi) }}</td>
                    <td>{{ $value($prescription->av_cerca_cc_oi) }}</td>
                    <td>{{ $value($prescription->av_cerca_ph_oi) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Subjetivo</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Esfera</th>
                    <th>Cilindro</th>
                    <th>Eje</th>
                    <th>Adición</th>
                    <th>DP</th>
                    <th>AV VL</th>
                    <th>AV VC</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>OD</strong></td>
                    <td>{{ $value($prescription->subjetivo_od_esfera) }}</td>
                    <td>{{ $value($prescription->subjetivo_od_cilindro) }}</td>
                    <td>{{ $value($prescription->subjetivo_od_eje) }}</td>
                    <td>{{ $value($prescription->subjetivo_od_add) }}</td>
                    <td>{{ $value($prescription->subjetivo_od_dp) }}</td>
                    <td>{{ $value($prescription->subjetivo_od_av_lejos) }}</td>
                    <td>{{ $value($prescription->subjetivo_od_av_cerca) }}</td>
                </tr>
                <tr>
                    <td><strong>OI</strong></td>
                    <td>{{ $value($prescription->subjetivo_oi_esfera) }}</td>
                    <td>{{ $value($prescription->subjetivo_oi_cilindro) }}</td>
                    <td>{{ $value($prescription->subjetivo_oi_eje) }}</td>
                    <td>{{ $value($prescription->subjetivo_oi_add) }}</td>
                    <td>{{ $value($prescription->subjetivo_oi_dp) }}</td>
                    <td>{{ $value($prescription->subjetivo_oi_av_lejos) }}</td>
                    <td>{{ $value($prescription->subjetivo_oi_av_cerca) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Oftalmoscopia</div>
        <table class="info-table">
            <tr>
                <td><strong>OD:</strong> {{ $value($prescription->oftalmoscopia_od_color) }} | {{ $value($prescription->oftalmoscopia_od_papila) }} | {{ $value($prescription->oftalmoscopia_od_macula) }}</td>
            </tr>
            <tr>
                <td><strong>OI:</strong> {{ $value($prescription->oftalmoscopia_oi_color) }} | {{ $value($prescription->oftalmoscopia_oi_papila) }} | {{ $value($prescription->oftalmoscopia_oi_macula) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Examen externo</div>
        <div>{!! $formatText($prescription->biomicroscopia_observaciones ?? $prescription->observaciones) !!}</div>
    </div>

    <div class="section">
        <div class="section-title">Examen motor</div>
        <table class="info-table">
            <tr>
                <td><strong>Test usado:</strong> {{ $value($prescription->motilidad_test_usado) }}</td>
                <td><strong>RFP VL:</strong> {{ $value($prescription->motilidad_rfp_vl) }}</td>
                <td><strong>RFP VC:</strong> {{ $value($prescription->motilidad_rfp_vc) }}</td>
            </tr>
            <tr>
                <td><strong>RFN VL:</strong> {{ $value($prescription->motilidad_rfn_vl) }}</td>
                <td><strong>RFN VC:</strong> {{ $value($prescription->motilidad_rfn_vc) }}</td>
                <td><strong>PPC:</strong> {{ $value($prescription->ppc_objeto_real) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Visión cromática</div>
        <div>{!! $formatText($prescription->vision_cromatica_interpretacion) !!}</div>
    </div>

    <div class="section">
        <div class="section-title">Estereopsis</div>
        <div>{{ $value($prescription->estereopsis_agudeza) }}</div>
    </div>

    <div class="section">
        <div class="section-title">Disposición</div>
        <div>{!! $formatText($prescription->disposicion) !!}</div>
    </div>

    <div class="section">
        <div class="section-title">Diagnóstico</div>
        <div>{!! $formatText($prescription->diagnosticos ?? $prescription->diagnostico_principal) !!}</div>
    </div>

    <div class="section">
        <div class="section-title">Recomendación</div>
        <div>{!! $formatText($prescription->recomendacion) !!}</div>
    </div>

    <div class="signature">
        <div class="signature-line">FIRMA PROFESIONAL</div>
    </div>
</body>
</html>
