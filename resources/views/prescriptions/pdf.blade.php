<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receta Médica - {{ $prescription->patient_name ?? 'José Isaías Pérez Gracia' }}</title>
    <style>


        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
            line-height: 1.3;
            color: #000;
            background: white;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .company-info {
            flex: 1;
            font-size: 12px;
            line-height: 1.3;
        }

        .company-address {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .company-phone {
            font-weight: normal;
        }

        .logo-section {
            flex: 0 0 auto;
            text-align: right;
            padding-left: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 15px;
        }

        th, td {
            text-align: center;
            padding: 6px;
        }

        th {
            font-weight: bold;
        }

        .header-info,
        .patient-section,
        .measurements-section,
        .additional-section {
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .prescription-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 20px;
        }

        .prescription-table th {
            background-color: #fff;
            color: #000;
            padding: 8px 4px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #000;
        }

        .prescription-table td {
            padding: 8px 4px;
            border: 1px solid #000;
            text-align: center;
        }

        .prescription-table .eye-label {
            background-color: #f5f5f5;
            text-align: left;
            padding-left: 10px;
        }

        .comments-section {
            margin-top: 15px;
            font-size: 12px;
            font-weight: bold;
        }

    </style>
</head>
<body>
    <!-- Header with company info and logo -->
    <div class="header">
        <div class="company-info">
            <div class="company-address">{{ $prescription->branch_address ?? 'Calle Restauración #42, frente al BanReservas, Salcedo' }}</div>
            <div class="company-phone">{{ $prescription->branch_phone ?? '(809) 577-1758' }}</div>
        </div>
        <div class="logo-section">
            <img src="<?php echo $_SERVER["DOCUMENT_ROOT"]."logo-covi.png"; ?>" style="height: 60px;">
        </div>
    </div>

    <!-- Header info section -->
    <div class="header-info">
        <table>
            <tr>
                <th>Fecha:</th>
                <th>Optómetra:</th>
                <th>Sucursal:</th>
            </tr>
            <tr>
                <td>{{ $prescription->date ?? '06/10/2025' }}</td>
                <td>{{ $prescription->optometrist?->name ?? 'Receta Externa' }}</td>
                <td>{{ $prescription->branch ?? 'Óptica COVI Salcedo' }}</td>
            </tr>
        </table>
    </div>

    <!-- Patient info section -->
    <div class="patient-section">
        <table>
            <tr>
                <th>Nombre:</th>
                <th>Edad:</th>
                <th>Género:</th>
                <th>ID:</th>
                <th>Tel.:</th>
            </tr>
            <tr>
                <td>{{ $prescription->patient_name ?? 'Jose Isaias Perez Gracia' }}</td>
                <td>{{ $prescription->patient_age ?? '8' }}</td>
                <td>{{ $prescription->patient_gender ?? 'Masculino' }}</td>
                <td>{{ $prescription->patient_id ?? '7183' }}</td>
                <td>{{ $prescription->patient_phone ?? '+18297587128' }}</td>
            </tr>
        </table>
    </div>

    <!-- Prescription table -->
    <table class="prescription-table">
        <thead>
            <tr>
                <th style="width: 20%;">OJO</th>
                <th style="width: 20%;">Esfera</th>
                <th style="width: 20%;">Cilindro</th>
                <th style="width: 20%;">Eje</th>
                <th style="width: 20%;">Adición</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="eye-label">Ojo derecho</td>
                <td>{{ $prescription->od_sphere ?? '-5.00' }}</td>
                <td>{{ $prescription->od_cylinder ?? '-2.75' }}</td>
                <td>{{ $prescription->od_axis ?? '168°' }}</td>
                <td>{{ $prescription->od_addition ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="eye-label">Ojo izquierdo</td>
                <td>{{ $prescription->os_sphere ?? '-5.50' }}</td>
                <td>{{ $prescription->os_cylinder ?? '-3.50' }}</td>
                <td>{{ $prescription->os_axis ?? '171°' }}</td>
                <td>{{ $prescription->os_addition ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Measurements section -->
    <div class="measurements-section">
        <table>
            <tr>
                <th>Distancia Pupilar AO:</th>
                <th>Distancia Naso Pupilar:</th>
                <th>Altura:</th>
            </tr>
            <tr>
                <td>{{ $prescription->pupillary_distance ?? '58mm' }}</td>
                <td>{{ $prescription->nasal_pupillary_distance ?? '29/29' }}</td>
                <td>{{ $prescription->height ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Additional boxes section (now table aligned) -->
    <div class="additional-section">
        <table>
            <tr>
                <th>Tipo de Lente:</th>
                <th>Montura:</th>
                <th>Gotas (Si Aplica):</th>
            </tr>
            <tr>
                <td>{{ $prescription->lens_type ?? 'Visión Sencilla' }}</td>
                <td>{{ $prescription->frame_type ?? 'Montura Redonda' }}</td>
                <td>{{ $prescription->drops ?? '' }}</td>
            </tr>
        </table>
    </div>

    <!-- Comments section -->
    @if($prescription->comments ?? 'VS POLI AR FILTRO AZUL')
        <div class="comments-section">
            <strong>Comentario:</strong> {{ $prescription->comments ?? 'VS POLI AR FILTRO AZUL' }}
        </div>
    @endif
</body>
</html>
