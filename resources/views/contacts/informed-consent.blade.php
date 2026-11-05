@php
    $mark = fn (bool $checked): string => $checked ? 'X' : '&nbsp;&nbsp;';

    $procedures = [
        [
            'name' => 'CICLOPLEJÍA',
            'explanation' => 'Aplicación de fármacos ciclopléjicos en los ojos para lograr parálisis de la acomodación.',
            'consequences' => ['Incoordinación muscular', 'Rubor', 'Prolongación del efecto', 'Calor'],
        ],
        [
            'name' => 'DILATACIÓN PUPILAR',
            'explanation' => 'Aplicación de fármacos midriáticos en los ojos para lograr la dilatación de la pupila y así mejorar la observación del fondo de ojo',
            'consequences' => ['Síndrome confusional', 'Prolongación del efecto', 'Dolor de cabeza', 'Aumento de la presión del ojo'],
        ],
        [
            'name' => 'TONOMETRÍA',
            'explanation' => 'Aplicación de anestésico tópico sobre la córnea para ubicar sobre ella los instrumentos de medición, tales como: tonómetro de Goldman, Schiotz o Perkins, para medir la presión intraocular.',
            'consequences' => ['Infección córnea', 'Raspado corneal'],
        ],
        [
            'name' => 'LENTES DE CONTACTO',
            'explanation' => 'Ubicación de lentes de contacto sobre la córnea o esclera, para determinar otra alternativa de corrección del defecto refractivo o mejorar la apariencia estética.',
            'consequences' => ['Raspado corneal', 'Hiperemia marcada', 'Trauma mecánico conjuntiva', 'Intolerancia al lente', 'Infección corneal'],
        ],
        [
            'name' => 'EXTRACCIÓN DE CUERPO EXTRAÑO',
            'explanation' => 'Aplicación de fármacos anestésicos en el ojo afectado y fluoresceína mediante un frotis con un hisopo o realizar debridación corneal.',
            'consequences' => ['Intolerancia', 'Ojo rojo', 'Blanqueamiento del ojo'],
        ],
        [
            'name' => 'APLICACIÓN DE FLUORESCEÍNA/VERDE LISAMINA',
            'explanation' => 'Aplicación de un colorante diagnóstico sobre la superficie ocular para evaluar alteraciones de la conjuntiva y córnea, especialmente células dañadas o desvitalizadas y cambios asociados a sequedad ocular.',
            'consequences' => ['Coloración temporal', 'Lagrimeo', 'Irritación leve transitoria.'],
        ],
        [
            'name' => 'TEST DE SCHIRMER',
            'explanation' => 'Introducir una tira de papel secante en cada párpado inferior durante 5 minutos para ayudar a determinar si el ojo produce suficientes lágrimas para mantenerlo húmedo.',
            'consequences' => ['Irritación ocular'],
        ],
        [
            'name' => 'EXTRACCIÓN DE PESTAÑA PARA EVALUACIÓN DE DEMODEX',
            'explanation' => 'Extracción controlada de una o varias pestañas para examen microscópico y detección de Demodex folliculorum o Demodex brevis',
            'consequences' => ['Molestia leve y transitoria,'],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consentimiento informado - {{ $patientName }}</title>
    <style>
        @page {
            margin: 22px 26px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Serif', serif;
            font-size: 9.5px;
            color: #000;
            line-height: 1.3;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            border: 1px solid #000;
            vertical-align: middle;
            text-align: center;
            padding: 2px 6px;
        }

        .header .title {
            font-weight: bold;
            font-size: 11px;
        }

        .header .meta {
            font-weight: bold;
            font-size: 11px;
        }

        .box {
            border: 1px solid #000;
            border-top: none;
            padding: 6px 8px;
        }

        .spacer {
            height: 10px;
        }

        .justify {
            text-align: justify;
        }

        .row {
            margin-top: 8px;
        }

        .row td {
            padding: 0;
            vertical-align: bottom;
        }

        .label {
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
            padding-right: 8px !important;
        }

        .fill {
            border-bottom: 1px solid #000;
            padding-left: 6px !important;
            height: 16px;
        }

        .procedures td,
        .procedures th {
            border: 1px solid #000;
            padding: 2px 6px;
            vertical-align: middle;
        }

        .procedures th {
            font-size: 11px;
            text-align: center;
        }

        .procedures th small {
            font-size: 5.5px;
            font-weight: bold;
        }

        .procedures .name {
            width: 26%;
        }

        .procedures .check {
            width: 3%;
        }

        .procedures .explanation {
            width: 45%;
            text-align: justify;
        }

        .procedures .consequences {
            width: 26%;
            line-height: 1.25;
        }

        .signatures {
            margin-top: 30px;
        }

        .signatures td {
            width: 50%;
            padding: 0 24px;
            text-align: center;
            vertical-align: top;
        }

        .signature-line {
            border-top: 1px solid #000;
            padding-top: 3px;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 26%;">
                @if (! empty($company['logo']))
                    <img src="{{ storage_path('app/public/'.$company['logo']) }}" alt="Logo" style="max-height: 58px; max-width: 170px;">
                @else
                    <strong>{{ $company['company_name'] ?? '' }}</strong>
                @endif
            </td>
            <td class="title" style="width: 48%;">
                UNIDAD DE SEGMENTO ANTERIOR Y CONTACTOLOGÍA<br>
                PRESTACIÓN DEL SERVICIO – OPTOMETRÍA<br>
                CONSENTIMIENTO INFORMADO PARA<br>
                PROCEDIMIENTOS OPTOMÉTRICOS
            </td>
            <td class="meta" style="width: 26%;">
                Versión: 01<br>
                Código: CL-OPT-001<br>
                PÁGINA 1 DE 1
            </td>
        </tr>
    </table>

    <div class="spacer"></div>

    <div class="box" style="border-top: 1px solid #000;">
        <p class="justify" style="margin: 0;">
            Autorización expresa del PACIENTE o de su REPRESENTANTE LEGAL para la realización de procedimientos optométricos que puedan implicar algún riesgo, molestia o efecto transitorio. Declaro haber recibido información clara y comprensible sobre la naturaleza y finalidad de los procedimientos, sus posibles beneficios, riesgos, alternativas y las posibles consecuencias de no realizarlos, así como sobre los derechos del paciente relacionados con su atención optométrica.
        </p>

        <table class="row">
            <tr>
                <td style="width: 22%;">Yo, como PACIENTE ( {!! $mark(! $isRepresentative) !!} )</td>
                <td style="width: 30%;">REPRESENTANTE LEGAL ( {!! $mark($isRepresentative) !!} )</td>
                <td style="width: 48%; text-align: right;">
                    Fecha de atención DD ( {{ $attentionDate->format('d') }} ) MM ( {{ $attentionDate->format('m') }} ) AA ( {{ $attentionDate->format('y') }} )
                </td>
            </tr>
        </table>

        <table class="row" style="margin-top: 14px;">
            <tr>
                <td class="label" style="width: 38%;">Nombres y apellidos del PACIENTE</td>
                <td class="fill">{{ $patientName }}</td>
            </tr>
        </table>

        <table class="row" style="margin-top: 12px;">
            <tr>
                <td class="label" style="width: 38%;">Nombres y apellidos del REPRESENTANTE LEGAL</td>
                <td class="fill">{{ $representativeName }}</td>
            </tr>
        </table>

        <table class="row" style="margin-top: 10px;">
            <tr>
                <td class="label" style="width: 22%;">Identificación</td>
                <td style="width: 30%;">
                    Cedula ( {!! $mark($documentType === 'cedula') !!} ) Pasaporte ( {!! $mark($documentType === 'pasaporte') !!} )
                </td>
                <td style="width: 48%;">Núm. ( {{ $documentNumber }} )</td>
            </tr>
        </table>
    </div>

    <table class="procedures">
        <tr>
            <th colspan="2" style="text-align: left;">PROCEDIMIENTO <small>(SELECCIONE CON UNA X)</small></th>
            <th>EXPLICACIÓN</th>
            <th>CONSECUENCIAS</th>
        </tr>
        @foreach ($procedures as $procedure)
            <tr>
                <td class="name">{{ $procedure['name'] }}</td>
                <td class="check"></td>
                <td class="explanation">{{ $procedure['explanation'] }}</td>
                <td class="consequences">
                    @foreach ($procedure['consequences'] as $consequence)
                        () {{ $consequence }}<br>
                    @endforeach
                </td>
            </tr>
        @endforeach
    </table>

    <div class="box">
        <p class="justify" style="margin: 4px 0 0;">
            Certifico que he sido debidamente informado(a) sobre la naturaleza y propósito de los procedimientos optométricos, sus posibles tratamientos, alternativas, riesgos y consecuencias. Autorizo al profesional a realizar o supervisar los procedimientos necesarios durante mi atención, entendiendo que buscan mejorar mi condición visual y ocular, pero que no garantizan un resultado específico. Asimismo, comprendo que pueden requerirse evaluaciones o procedimientos complementarios en otro centro especializado y declaro haber recibido las orientaciones necesarias para mi cuidado y seguimiento. Autorizo el uso anonimizado de mis datos clínicos e imágenes con fines académicos y científicos, garantizando la confidencialidad de mi identidad. La información será tratada conforme a la Ley núm. 172-13 sobre Protección Integral de los Datos Personales y al derecho a la intimidad reconocido en el artículo 44 de la Constitución de la República Dominicana.
        </p>

        <table class="row" style="margin-top: 14px;">
            <tr>
                <td style="width: 60%; padding-left: 80px;">Autorizo a: &nbsp;&nbsp; Si (&nbsp;&nbsp;&nbsp;) No (&nbsp;&nbsp;&nbsp;)</td>
                <td style="width: 40%;">HC. N# {{ $clinicalRecordNumber }}</td>
            </tr>
        </table>

        <table class="signatures">
            <tr>
                <td><div class="signature-line">Firma del paciente o representante legal</div></td>
                <td><div class="signature-line">Firma y sello del profesional</div></td>
            </tr>
        </table>
        <div style="height: 6px;"></div>
    </div>
</body>
</html>
