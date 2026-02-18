<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia de optometría - {{ $prescription->patient->name }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #111;
            margin: 20px 26px;
            line-height: 1.35;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #2c5aa0;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.4px;
        }

        .company-meta {
            font-size: 8.5px;
            margin-top: 4px;
        }

        .logo-cell {
            text-align: center;
            vertical-align: middle;
            padding: 0 12px;
        }

        .meta-right {
            text-align: right;
            font-size: 8.5px;
        }

        .title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 8px 0 12px;
        }

        .section {
            margin-top: 8px;
            page-break-inside: avoid;
        }

        .section-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .section-grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }

        .section-grid td + td {
            padding-left: 8px;
            padding-right: 0;
        }

        .section-grid .section:first-child {
            margin-top: 0;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            border-bottom: 1px solid #333;
            margin-bottom: 4px;
            padding-bottom: 2px;
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
            font-size: 8.5px;
        }

        .data-table th {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5px;
            background: #f3f5f7;
        }

        .placeholder {
            border: 1px dashed #888;
            background: #f8f8f8;
            color: #555;
            font-style: italic;
            padding: 4px 6px;
            font-size: 8.5px;
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
            font-size: 8.5px;
        }
    </style>
</head>
<body>
    @php
        $value = fn ($value): string => filled($value) ? (string) $value : '-';
        $boolValue = fn ($value): string => $value ? 'Sí' : 'No';
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
        $jsonValue = function ($raw) use ($value): string {
            if (! filled($raw)) {
                return '-';
            }

            if (is_array($raw)) {
                return collect($raw)->flatten()->filter()->join(', ');
            }

            if (is_string($raw)) {
                $decoded = json_decode($raw, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return collect($decoded)->flatten()->filter()->join(', ');
                }
            }

            return (string) $raw;
        };
        $formatText = fn ($text): string => filled($text) ? nl2br(e((string) $text)) : '-';
        $hasValue = function ($value): bool {
            if ($value instanceof \Illuminate\Support\Collection) {
                return $value->isNotEmpty();
            }

            if (is_array($value)) {
                return collect($value)->flatten()->filter(fn ($item) => filled($item))->isNotEmpty();
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return collect($decoded)->flatten()->filter(fn ($item) => filled($item))->isNotEmpty();
                }

                return filled($value);
            }

            if (is_int($value) || is_float($value)) {
                return true;
            }

            if (is_bool($value)) {
                return $value;
            }

            return filled($value);
        };
        $hasAny = function (array $values) use ($hasValue): bool {
            foreach ($values as $value) {
                if ($hasValue($value)) {
                    return true;
                }
            }

            return false;
        };
        $placeholderText = 'Sin datos relevantes';
        $hasClinicalHistory = $hasAny([
            $prescription->motivos,
            $prescription->motivos_consulta_otros,
            $prescription->estadoActual,
            $prescription->estado_salud_actual_otros,
            $prescription->historiaOcularFamiliar,
            $prescription->historia_ocular_familiar_otros,
        ]);
        $hasLensometria = $hasAny([
            $prescription->lensometria_od,
            $prescription->lensometria_oi,
            $prescription->lensometria_add,
        ]);
        $hasAgudezaVisual = $hasAny([
            $prescription->av_lejos_sc_od,
            $prescription->av_lejos_cc_od,
            $prescription->av_lejos_ph_od,
            $prescription->av_lejos_sc_oi,
            $prescription->av_lejos_cc_oi,
            $prescription->av_lejos_ph_oi,
            $prescription->av_cerca_sc_od,
            $prescription->av_cerca_cc_od,
            $prescription->av_cerca_ph_od,
            $prescription->av_cerca_sc_oi,
            $prescription->av_cerca_cc_oi,
            $prescription->av_cerca_ph_oi,
        ]);
        $hasBiomicroscopia = $hasAny([
            $prescription->biomicroscopia_od_cejas,
            $prescription->biomicroscopia_od_pestanas,
            $prescription->biomicroscopia_od_parpados,
            $prescription->biomicroscopia_od_conjuntiva,
            $prescription->biomicroscopia_od_esclerotica,
            $prescription->biomicroscopia_od_cornea,
            $prescription->biomicroscopia_od_iris,
            $prescription->biomicroscopia_od_pupila,
            $prescription->biomicroscopia_od_cristalino,
            $prescription->biomicroscopia_oi_cejas,
            $prescription->biomicroscopia_oi_pestanas,
            $prescription->biomicroscopia_oi_parpados,
            $prescription->biomicroscopia_oi_conjuntiva,
            $prescription->biomicroscopia_oi_esclerotica,
            $prescription->biomicroscopia_oi_cornea,
            $prescription->biomicroscopia_oi_iris,
            $prescription->biomicroscopia_oi_pupila,
            $prescription->biomicroscopia_oi_cristalino,
            $prescription->biomicroscopia_observaciones,
        ]);
        $hasExamenPupilar = $hasAny([
            $prescription->pupilar_od_fotomotor_directo,
            $prescription->pupilar_od_consensual,
            $prescription->pupilar_od_acomodativo,
            $prescription->pupilar_oi_fotomotor_directo,
            $prescription->pupilar_oi_consensual,
            $prescription->pupilar_oi_acomodativo,
        ]);
        $hasOftalmoscopia = $hasAny([
            $prescription->oftalmoscopia_od_color,
            $prescription->oftalmoscopia_od_papila,
            $prescription->oftalmoscopia_od_excavacion,
            $prescription->oftalmoscopia_od_relacion_av,
            $prescription->oftalmoscopia_od_macula,
            $prescription->oftalmoscopia_od_brillo_foveal,
            $prescription->oftalmoscopia_od_fijacion,
            $prescription->oftalmoscopia_oi_color,
            $prescription->oftalmoscopia_oi_papila,
            $prescription->oftalmoscopia_oi_excavacion,
            $prescription->oftalmoscopia_oi_relacion_av,
            $prescription->oftalmoscopia_oi_macula,
            $prescription->oftalmoscopia_oi_brillo_foveal,
            $prescription->oftalmoscopia_oi_fijacion,
            $prescription->oftalmoscopia_observaciones,
        ]);
        $hasQueratometria = $hasAny([
            $prescription->quera_od_horizontal,
            $prescription->quera_od_vertical,
            $prescription->quera_od_eje,
            $prescription->quera_od_dif,
            $prescription->quera_oi_horizontal,
            $prescription->quera_oi_vertical,
            $prescription->quera_oi_eje,
            $prescription->quera_oi_dif,
        ]);
        $hasPresionIntraocular = $hasAny([
            $prescription->presion_od,
            $prescription->presion_od_hora,
            $prescription->presion_oi,
            $prescription->presion_oi_hora,
        ]);
        $hasCicloplegia = $hasAny([
            $prescription->cicloplegia_medicamento,
            $prescription->cicloplegia_num_gotas,
            $prescription->cicloplegia_hora_aplicacion,
            $prescription->cicloplegia_hora_examen,
        ]);
        $hasAutorefraccion = $hasAny([
            $prescription->autorefraccion_od_esfera,
            $prescription->autorefraccion_od_cilindro,
            $prescription->autorefraccion_od_eje,
            $prescription->autorefraccion_oi_esfera,
            $prescription->autorefraccion_oi_cilindro,
            $prescription->autorefraccion_oi_eje,
        ]);
        $hasRefraccion = $hasAny([
            $prescription->refraccion_od_esfera,
            $prescription->refraccion_od_cilindro,
            $prescription->refraccion_od_eje,
            $prescription->refraccion_subjetivo_od_adicion,
            $prescription->refraccion_oi_esfera,
            $prescription->refraccion_oi_cilindro,
            $prescription->refraccion_oi_eje,
            $prescription->refraccion_subjetivo_oi_adicion,
            $prescription->refraccion_observaciones,
        ]);
        $hasRetinoscopia = $hasAny([
            $prescription->retinoscopia_od_esfera,
            $prescription->retinoscopia_od_cilindro,
            $prescription->retinoscopia_od_eje,
            $prescription->retinoscopia_oi_esfera,
            $prescription->retinoscopia_oi_cilindro,
            $prescription->retinoscopia_oi_eje,
            $prescription->retinoscopia_estatica,
            $prescription->retinoscopia_dinamica,
        ]);
        $hasSubjetivoRefraccion = $hasAny([
            $prescription->refraccion_subjetivo_od_esfera,
            $prescription->refraccion_subjetivo_od_cilindro,
            $prescription->refraccion_subjetivo_od_eje,
            $prescription->refraccion_subjetivo_oi_esfera,
            $prescription->refraccion_subjetivo_oi_cilindro,
            $prescription->refraccion_subjetivo_oi_eje,
        ]);
        $hasSubjetivoFinal = $hasAny([
            $prescription->subjetivo_od_esfera,
            $prescription->subjetivo_od_cilindro,
            $prescription->subjetivo_od_eje,
            $prescription->subjetivo_od_add,
            $prescription->subjetivo_od_dp,
            $prescription->subjetivo_od_av_lejos,
            $prescription->subjetivo_od_av_cerca,
            $prescription->subjetivo_oi_esfera,
            $prescription->subjetivo_oi_cilindro,
            $prescription->subjetivo_oi_eje,
            $prescription->subjetivo_oi_add,
            $prescription->subjetivo_oi_dp,
            $prescription->subjetivo_oi_av_lejos,
            $prescription->subjetivo_oi_av_cerca,
        ]);
        $hasVisionCromatica = $hasAny([
            $prescription->vision_cromatica_test_usado,
            $prescription->vision_cromatica_od,
            $prescription->vision_cromatica_oi,
            $prescription->vision_cromatica_interpretacion,
        ]);
        $hasEstereopsis = $hasAny([
            $prescription->estereopsis_test_usado,
            $prescription->estereopsis_agudeza,
        ]);
        $hasTonometria = $hasAny([
            $prescription->tonometria_metodo,
            $prescription->tonometria_hora,
            $prescription->tonometria_tonometro,
            $prescription->tonometria_od,
            $prescription->tonometria_oi,
        ]);
        $hasTestAdicionales = $hasAny([
            $prescription->test_adicionales,
        ]);
        $hasMotilidadOcular = $hasAny([
            $prescription->ojo_dominante,
            $prescription->mano_dominante,
            $prescription->kappa_od,
            $prescription->kappa_oi,
            $prescription->ducciones_od,
            $prescription->ducciones_oi,
            $prescription->hirshberg,
            $prescription->versiones_grid,
            $prescription->motilidad_test_usado,
            $prescription->motilidad_rfp_vl,
            $prescription->motilidad_rfp_vc,
            $prescription->motilidad_rfn_vl,
            $prescription->motilidad_rfn_vc,
            $prescription->motilidad_saltos_vergenciales_vl,
            $prescription->motilidad_saltos_vergenciales_vc,
            $prescription->ppc_objeto_real,
            $prescription->ppc_luz,
            $prescription->ppc_filtro_rojo,
            $prescription->lag_od_acomodacion,
            $prescription->lag_od_flexibilidad,
            $prescription->lag_oi_acomodacion,
            $prescription->lag_oi_flexibilidad,
            $prescription->arp_subjetiva,
            $prescription->arp_objetiva,
            $prescription->arn_amplitud,
            $prescription->ao_valor,
            $prescription->ao_aa,
            $prescription->motilidad_observaciones,
        ]);
        $hasDisposicionObservaciones = $hasAny([
            $prescription->disposicion,
            $prescription->observaciones,
            $prescription->observaciones_internas,
            $prescription->recomendacion,
        ]);
        $hasDiagnostico = $hasAny([
            $prescription->tipo_diagnostico,
            $prescription->diagnostico_codigo,
            $prescription->diagnostico_tipo,
            $prescription->diagnostico_principal,
            $prescription->num_dispositivos_medicos,
            $prescription->diagnosticos,
            $prescription->diagnosticos_cie,
        ]);
        $hasRecomendaciones = $hasAny([
            $prescription->lentesRecomendados,
            $prescription->monturasRecomendadas,
            $prescription->gotasRecomendadas,
            $prescription->canalesDeReferimiento,
        ]);
        $hasProximaCita = $hasAny([
            $prescription->proxima_cita,
        ]);
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

    <div class="title">Historia de optometría</div>

    <table class="info-table">
        <tr>
            <td><strong>Paciente:</strong> {{ strtoupper($prescription->patient->name) }}</td>
            <td><strong>Documento:</strong> {{ $value($prescription->patient->identification_number) }}</td>
            <td><strong>Edad:</strong> {{ $value($prescription->patient->age ?? null) }}</td>
            <td><strong>Teléfono:</strong> {{ $value($prescription->patient->phone_primary) }}</td>
        </tr>
        <tr>
            <td><strong>Celular:</strong> {{ $value($prescription->patient->mobile) }}</td>
            <td colspan="2"><strong>Dirección:</strong> {{ $value($prescription->patient->full_address ?? $prescription->patient->address ?? null) }}</td>
            <td><strong>Evaluador:</strong> {{ $value($prescription->optometrist?->name) }}</td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Historial clínico</div>
        @if ($hasClinicalHistory)
            <table class="info-table">
                <tr>
                    <td><strong>Motivo de consulta:</strong> {!! $formatText($listWithOtros($prescription->motivos, $prescription->motivos_consulta_otros)) !!}</td>
                </tr>
                <tr>
                    <td><strong>Estado salud actual:</strong> {!! $formatText($listWithOtros($prescription->estadoActual, $prescription->estado_salud_actual_otros)) !!}</td>
                </tr>
                <tr>
                    <td><strong>Historia ocular familiar:</strong> {!! $formatText($listWithOtros($prescription->historiaOcularFamiliar, $prescription->historia_ocular_familiar_otros)) !!}</td>
                </tr>
            </table>
        @else
            <div class="placeholder">{{ $placeholderText }}</div>
        @endif
    </div>

    <table class="section-grid">
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">Lensometría</div>
                    @if ($hasLensometria)
                        <table class="info-table">
                            <tr>
                                <td><strong>OD:</strong> {{ $value($prescription->lensometria_od) }}</td>
                                <td><strong>OI:</strong> {{ $value($prescription->lensometria_oi) }}</td>
                                <td><strong>Add:</strong> {{ $value($prescription->lensometria_add) }}</td>
                            </tr>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
                <div class="section">
                    <div class="section-title">Agudeza visual</div>
                    @if ($hasAgudezaVisual)
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
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="section">
                    <div class="section-title">Biomicroscopía</div>
                    @if ($hasBiomicroscopia)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Campo</th>
                                    <th>OD</th>
                                    <th>OI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Cejas</td><td>{{ $value($prescription->biomicroscopia_od_cejas) }}</td><td>{{ $value($prescription->biomicroscopia_oi_cejas) }}</td></tr>
                                <tr><td>Pestañas</td><td>{{ $value($prescription->biomicroscopia_od_pestanas) }}</td><td>{{ $value($prescription->biomicroscopia_oi_pestanas) }}</td></tr>
                                <tr><td>Párpados</td><td>{{ $value($prescription->biomicroscopia_od_parpados) }}</td><td>{{ $value($prescription->biomicroscopia_oi_parpados) }}</td></tr>
                                <tr><td>Conjuntiva</td><td>{{ $value($prescription->biomicroscopia_od_conjuntiva) }}</td><td>{{ $value($prescription->biomicroscopia_oi_conjuntiva) }}</td></tr>
                                <tr><td>Esclerótica</td><td>{{ $value($prescription->biomicroscopia_od_esclerotica) }}</td><td>{{ $value($prescription->biomicroscopia_oi_esclerotica) }}</td></tr>
                                <tr><td>Córnea</td><td>{{ $value($prescription->biomicroscopia_od_cornea) }}</td><td>{{ $value($prescription->biomicroscopia_oi_cornea) }}</td></tr>
                                <tr><td>Iris</td><td>{{ $value($prescription->biomicroscopia_od_iris) }}</td><td>{{ $value($prescription->biomicroscopia_oi_iris) }}</td></tr>
                                <tr><td>Pupila</td><td>{{ $value($prescription->biomicroscopia_od_pupila) }}</td><td>{{ $value($prescription->biomicroscopia_oi_pupila) }}</td></tr>
                                <tr><td>Cristalino</td><td>{{ $value($prescription->biomicroscopia_od_cristalino) }}</td><td>{{ $value($prescription->biomicroscopia_oi_cristalino) }}</td></tr>
                                <tr><td>Observaciones</td><td colspan="2">{!! $formatText($prescription->biomicroscopia_observaciones) !!}</td></tr>
                            </tbody>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
                <div class="section">
                    <div class="section-title">Examen pupilar</div>
                    @if ($hasExamenPupilar)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Campo</th>
                                    <th>OD</th>
                                    <th>OI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Fotomotor directo</td><td>{{ $value($prescription->pupilar_od_fotomotor_directo) }}</td><td>{{ $value($prescription->pupilar_oi_fotomotor_directo) }}</td></tr>
                                <tr><td>Consensual</td><td>{{ $value($prescription->pupilar_od_consensual) }}</td><td>{{ $value($prescription->pupilar_oi_consensual) }}</td></tr>
                                <tr><td>Acomodativo</td><td>{{ $value($prescription->pupilar_od_acomodativo) }}</td><td>{{ $value($prescription->pupilar_oi_acomodativo) }}</td></tr>
                            </tbody>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
                <div class="section">
                    <div class="section-title">Oftalmoscopía</div>
                    @if ($hasOftalmoscopia)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Campo</th>
                                    <th>OD</th>
                                    <th>OI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Color</td><td>{{ $value($prescription->oftalmoscopia_od_color) }}</td><td>{{ $value($prescription->oftalmoscopia_oi_color) }}</td></tr>
                                <tr><td>Papila</td><td>{{ $value($prescription->oftalmoscopia_od_papila) }}</td><td>{{ $value($prescription->oftalmoscopia_oi_papila) }}</td></tr>
                                <tr><td>Excavación</td><td>{{ $value($prescription->oftalmoscopia_od_excavacion) }}</td><td>{{ $value($prescription->oftalmoscopia_oi_excavacion) }}</td></tr>
                                <tr><td>Relación A/V</td><td>{{ $value($prescription->oftalmoscopia_od_relacion_av) }}</td><td>{{ $value($prescription->oftalmoscopia_oi_relacion_av) }}</td></tr>
                                <tr><td>Mácula</td><td>{{ $value($prescription->oftalmoscopia_od_macula) }}</td><td>{{ $value($prescription->oftalmoscopia_oi_macula) }}</td></tr>
                                <tr><td>Brillo foveal</td><td>{{ $value($prescription->oftalmoscopia_od_brillo_foveal) }}</td><td>{{ $value($prescription->oftalmoscopia_oi_brillo_foveal) }}</td></tr>
                                <tr><td>Fijación</td><td>{{ $value($prescription->oftalmoscopia_od_fijacion) }}</td><td>{{ $value($prescription->oftalmoscopia_oi_fijacion) }}</td></tr>
                                <tr><td>Observaciones</td><td colspan="2">{!! $formatText($prescription->oftalmoscopia_observaciones) !!}</td></tr>
                            </tbody>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="section-grid">
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">Queratometría</div>
                    @if ($hasQueratometria)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Campo</th>
                                    <th>OD</th>
                                    <th>OI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Horizontal</td><td>{{ $value($prescription->quera_od_horizontal) }}</td><td>{{ $value($prescription->quera_oi_horizontal) }}</td></tr>
                                <tr><td>Vertical</td><td>{{ $value($prescription->quera_od_vertical) }}</td><td>{{ $value($prescription->quera_oi_vertical) }}</td></tr>
                                <tr><td>Eje</td><td>{{ $value($prescription->quera_od_eje) }}</td><td>{{ $value($prescription->quera_oi_eje) }}</td></tr>
                                <tr><td>Diferencial</td><td>{{ $value($prescription->quera_od_dif) }}</td><td>{{ $value($prescription->quera_oi_dif) }}</td></tr>
                            </tbody>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
                <div class="section">
                    <div class="section-title">Presión intraocular</div>
                    @if ($hasPresionIntraocular)
                        <table class="info-table">
                            <tr>
                                <td><strong>OD:</strong> {{ $value($prescription->presion_od) }}</td>
                                <td><strong>Hora OD:</strong> {{ $value($prescription->presion_od_hora) }}</td>
                                <td><strong>OI:</strong> {{ $value($prescription->presion_oi) }}</td>
                                <td><strong>Hora OI:</strong> {{ $value($prescription->presion_oi_hora) }}</td>
                            </tr>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="section">
                    <div class="section-title">Refracción</div>
                    @if ($hasRefraccion)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Esfera</th>
                                    <th>Cilindro</th>
                                    <th>Eje</th>
                                    <th>Adición</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>OD</strong></td>
                                    <td>{{ $value($prescription->refraccion_od_esfera) }}</td>
                                    <td>{{ $value($prescription->refraccion_od_cilindro) }}</td>
                                    <td>{{ $value($prescription->refraccion_od_eje) }}</td>
                                    <td>{{ $value($prescription->refraccion_subjetivo_od_adicion) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>OI</strong></td>
                                    <td>{{ $value($prescription->refraccion_oi_esfera) }}</td>
                                    <td>{{ $value($prescription->refraccion_oi_cilindro) }}</td>
                                    <td>{{ $value($prescription->refraccion_oi_eje) }}</td>
                                    <td>{{ $value($prescription->refraccion_subjetivo_oi_adicion) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5"><strong>Observaciones:</strong> {!! $formatText($prescription->refraccion_observaciones) !!}</td>
                                </tr>
                            </tbody>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="section-grid">
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">Cicloplegia</div>
                    @if ($hasCicloplegia)
                        <table class="info-table">
                            <tr>
                                <td><strong>Medicamento:</strong> {{ $value($prescription->cicloplegia_medicamento) }}</td>
                                <td><strong>Número gotas:</strong> {{ $value($prescription->cicloplegia_num_gotas) }}</td>
                                <td><strong>Hora aplicación:</strong> {{ $value($prescription->cicloplegia_hora_aplicacion) }}</td>
                                <td><strong>Hora examen:</strong> {{ $value($prescription->cicloplegia_hora_examen) }}</td>
                            </tr>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
                <div class="section">
                    <div class="section-title">Autorefracción</div>
                    @if ($hasAutorefraccion)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Esfera</th>
                                    <th>Cilindro</th>
                                    <th>Eje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>OD</strong></td>
                                    <td>{{ $value($prescription->autorefraccion_od_esfera) }}</td>
                                    <td>{{ $value($prescription->autorefraccion_od_cilindro) }}</td>
                                    <td>{{ $value($prescription->autorefraccion_od_eje) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>OI</strong></td>
                                    <td>{{ $value($prescription->autorefraccion_oi_esfera) }}</td>
                                    <td>{{ $value($prescription->autorefraccion_oi_cilindro) }}</td>
                                    <td>{{ $value($prescription->autorefraccion_oi_eje) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="section">
                    <div class="section-title">Retinoscopía</div>
                    @if ($hasRetinoscopia)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Esfera</th>
                                    <th>Cilindro</th>
                                    <th>Eje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>OD</strong></td>
                                    <td>{{ $value($prescription->retinoscopia_od_esfera) }}</td>
                                    <td>{{ $value($prescription->retinoscopia_od_cilindro) }}</td>
                                    <td>{{ $value($prescription->retinoscopia_od_eje) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>OI</strong></td>
                                    <td>{{ $value($prescription->retinoscopia_oi_esfera) }}</td>
                                    <td>{{ $value($prescription->retinoscopia_oi_cilindro) }}</td>
                                    <td>{{ $value($prescription->retinoscopia_oi_eje) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2"><strong>Estática:</strong> {{ $boolValue($prescription->retinoscopia_estatica) }}</td>
                                    <td colspan="2"><strong>Dinámica:</strong> {{ $boolValue($prescription->retinoscopia_dinamica) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
                <div class="section">
                    <div class="section-title">Subjetivo (refracción)</div>
                    @if ($hasSubjetivoRefraccion)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Esfera</th>
                                    <th>Cilindro</th>
                                    <th>Eje</th>
                                    <th>Adición</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>OD</strong></td>
                                    <td>{{ $value($prescription->refraccion_subjetivo_od_esfera) }}</td>
                                    <td>{{ $value($prescription->refraccion_subjetivo_od_cilindro) }}</td>
                                    <td>{{ $value($prescription->refraccion_subjetivo_od_eje) }}</td>
                                    <td>{{ $value($prescription->refraccion_subjetivo_od_adicion) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>OI</strong></td>
                                    <td>{{ $value($prescription->refraccion_subjetivo_oi_esfera) }}</td>
                                    <td>{{ $value($prescription->refraccion_subjetivo_oi_cilindro) }}</td>
                                    <td>{{ $value($prescription->refraccion_subjetivo_oi_eje) }}</td>
                                    <td>{{ $value($prescription->refraccion_subjetivo_oi_adicion) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="section-grid">
        <tr>
            <td colspan="2">
                <div class="section">
                    <div class="section-title">Subjetivo final</div>
                    @if ($hasSubjetivoFinal)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Esfera</th>
                                    <th>Cilindro</th>
                                    <th>Eje</th>
                                    <th>Add</th>
                                    <th>DP</th>
                                    <th>AV Lejos</th>
                                    <th>AV Cerca</th>
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
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="section-grid">
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">Visión cromática</div>
                    @if ($hasVisionCromatica)
                        <table class="info-table">
                            <tr>
                                <td><strong>Test usado:</strong> {{ $value($prescription->vision_cromatica_test_usado) }}</td>
                                <td><strong>OD:</strong> {!! $formatText($prescription->vision_cromatica_od) !!}</td>
                                <td><strong>OI:</strong> {!! $formatText($prescription->vision_cromatica_oi) !!}</td>
                                <td><strong>Interpretación:</strong> {!! $formatText($prescription->vision_cromatica_interpretacion) !!}</td>
                            </tr>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
                <div class="section">
                    <div class="section-title">Estereopsis</div>
                    @if ($hasEstereopsis)
                        <table class="info-table">
                            <tr>
                                <td><strong>Test usado:</strong> {{ $value($prescription->estereopsis_test_usado) }}</td>
                                <td><strong>Agudeza:</strong> {!! $formatText($prescription->estereopsis_agudeza) !!}</td>
                            </tr>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="section">
                    <div class="section-title">Tonometría</div>
                    @if ($hasTonometria)
                        <table class="info-table">
                            <tr>
                                <td><strong>Método:</strong> {{ $value($prescription->tonometria_metodo) }}</td>
                                <td><strong>Hora:</strong> {{ $value($prescription->tonometria_hora) }}</td>
                                <td><strong>Tonómetro:</strong> {!! $formatText($prescription->tonometria_tonometro) !!}</td>
                                <td><strong>OD:</strong> {!! $formatText($prescription->tonometria_od) !!}</td>
                                <td><strong>OI:</strong> {!! $formatText($prescription->tonometria_oi) !!}</td>
                            </tr>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
                <div class="section">
                    <div class="section-title">Test adicionales</div>
                    @if ($hasTestAdicionales)
                        <div>{!! $formatText($prescription->test_adicionales) !!}</div>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="section-grid">
        <tr>
            <td colspan="2">
                <div class="section">
                    <div class="section-title">Motilidad ocular</div>
                    @if ($hasMotilidadOcular)
                        <table class="info-table">
                            <tr>
                                <td><strong>Ojo dominante:</strong> {{ $value($prescription->ojo_dominante) }}</td>
                                <td><strong>Mano dominante:</strong> {{ $value($prescription->mano_dominante) }}</td>
                                <td><strong>Kappa OD:</strong> {{ $value($prescription->kappa_od) }}</td>
                                <td><strong>Kappa OI:</strong> {{ $value($prescription->kappa_oi) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Ducciones OD:</strong> {!! $formatText($prescription->ducciones_od) !!}</td>
                                <td><strong>Ducciones OI:</strong> {!! $formatText($prescription->ducciones_oi) !!}</td>
                                <td><strong>Hirshberg:</strong> {{ $value($prescription->hirshberg) }}</td>
                                <td><strong>Versiones:</strong> {{ $jsonValue($prescription->versiones_grid) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Test usado:</strong> {{ $value($prescription->motilidad_test_usado) }}</td>
                                <td><strong>RFP VL:</strong> {{ $value($prescription->motilidad_rfp_vl) }}</td>
                                <td><strong>RFP VC:</strong> {{ $value($prescription->motilidad_rfp_vc) }}</td>
                                <td><strong>RFN VL:</strong> {{ $value($prescription->motilidad_rfn_vl) }}</td>
                            </tr>
                            <tr>
                                <td><strong>RFN VC:</strong> {{ $value($prescription->motilidad_rfn_vc) }}</td>
                                <td><strong>Saltos VL:</strong> {{ $value($prescription->motilidad_saltos_vergenciales_vl) }}</td>
                                <td><strong>Saltos VC:</strong> {{ $value($prescription->motilidad_saltos_vergenciales_vc) }}</td>
                                <td><strong>PPC (objeto real):</strong> {{ $value($prescription->ppc_objeto_real) }}</td>
                            </tr>
                            <tr>
                                <td><strong>PPC (luz):</strong> {{ $value($prescription->ppc_luz) }}</td>
                                <td><strong>PPC (filtro rojo):</strong> {{ $value($prescription->ppc_filtro_rojo) }}</td>
                                <td><strong>LAG OD acomodación:</strong> {{ $value($prescription->lag_od_acomodacion) }}</td>
                                <td><strong>LAG OD flexibilidad:</strong> {{ $value($prescription->lag_od_flexibilidad) }}</td>
                            </tr>
                            <tr>
                                <td><strong>LAG OI acomodación:</strong> {{ $value($prescription->lag_oi_acomodacion) }}</td>
                                <td><strong>LAG OI flexibilidad:</strong> {{ $value($prescription->lag_oi_flexibilidad) }}</td>
                                <td><strong>ARP subjetiva:</strong> {{ $value($prescription->arp_subjetiva) }}</td>
                                <td><strong>ARP objetiva:</strong> {{ $value($prescription->arp_objetiva) }}</td>
                            </tr>
                            <tr>
                                <td><strong>ARN amplitud:</strong> {{ $value($prescription->arn_amplitud) }}</td>
                                <td><strong>AO valor:</strong> {{ $value($prescription->ao_valor) }}</td>
                                <td><strong>AO A/A:</strong> {{ $value($prescription->ao_aa) }}</td>
                                <td><strong>Observaciones:</strong> {!! $formatText($prescription->motilidad_observaciones) !!}</td>
                            </tr>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="section-grid">
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">Disposición y observaciones</div>
                    @if ($hasDisposicionObservaciones)
                        <table class="info-table">
                            <tr>
                                <td><strong>Disposición:</strong> {!! $formatText($prescription->disposicion) !!}</td>
                            </tr>
                            <tr>
                                <td><strong>Observaciones:</strong> {!! $formatText($prescription->observaciones) !!}</td>
                            </tr>
                            <tr>
                                <td><strong>Observaciones internas:</strong> {!! $formatText($prescription->observaciones_internas) !!}</td>
                            </tr>
                            <tr>
                                <td><strong>Recomendación:</strong> {!! $formatText($prescription->recomendacion) !!}</td>
                            </tr>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="section">
                    <div class="section-title">Diagnóstico</div>
                    @if ($hasDiagnostico)
                        <table class="info-table">
                            <tr>
                                <td><strong>Tipo diagnóstico:</strong> {{ $value($prescription->tipo_diagnostico) }}</td>
                                <td><strong>Código:</strong> {{ $value($prescription->diagnostico_codigo) }}</td>
                                <td><strong>Tipo:</strong> {{ $value($prescription->diagnostico_tipo) }}</td>
                                <td><strong>Principal:</strong> {{ $value($prescription->diagnostico_principal) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Número dispositivos médicos:</strong> {{ $value($prescription->num_dispositivos_medicos) }}</td>
                                <td colspan="3"><strong>Diagnósticos:</strong> {!! $formatText($prescription->diagnosticos) !!}</td>
                            </tr>
                            <tr>
                                <td colspan="4"><strong>Diagnósticos CIE:</strong> {{ $jsonValue($prescription->diagnosticos_cie) }}</td>
                            </tr>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="section-grid">
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">Recomendaciones adicionales</div>
                    @if ($hasRecomendaciones)
                        <table class="info-table">
                            <tr>
                                <td><strong>Lentes recomendados:</strong> {!! $formatText($listValue($prescription->lentesRecomendados)) !!}</td>
                            </tr>
                            <tr>
                                <td><strong>Monturas recomendadas:</strong> {!! $formatText($listValue($prescription->monturasRecomendadas)) !!}</td>
                            </tr>
                            <tr>
                                <td><strong>Gotas recomendadas:</strong> {!! $formatText($listValue($prescription->gotasRecomendadas)) !!}</td>
                            </tr>
                            <tr>
                                <td><strong>Canales de referimiento:</strong> {!! $formatText($listValue($prescription->canalesDeReferimiento)) !!}</td>
                            </tr>
                        </table>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="section">
                    <div class="section-title">Próxima cita</div>
                    @if ($hasProximaCita)
                        <div>
                            {{ $prescription->proxima_cita ? \Carbon\Carbon::parse($prescription->proxima_cita)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : '-' }}
                        </div>
                    @else
                        <div class="placeholder">{{ $placeholderText }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="signature">
        <div class="signature-line">FIRMA PROFESIONAL</div>
    </div>
</body>
</html>
