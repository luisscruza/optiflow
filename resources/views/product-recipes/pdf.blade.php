<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recetario de productos - {{ $productRecipe->contact->name }}</title>
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
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #2c5aa0;
        }

        .company-name {
            font-size: 17px;
            font-weight: bold;
            color: #000;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .contact-info {
            font-size: 9px;
            margin-bottom: 2px;
            color: #000;
            line-height: 1.35;
        }

        .logo-cell {
            text-align: center;
            vertical-align: middle;
            padding: 0 10px;
        }

        .meta-box {
            font-size: 9px;
            text-align: right;
            color: #444;
            line-height: 1.45;
        }

        .meta-box strong {
            color: #000;
        }

        .title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin: 14px 0 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #000;
        }

        .patient-info {
            font-size: 10px;
            margin-bottom: 14px;
            padding: 10px 12px;
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }

        .patient-line {
            margin-bottom: 4px;
            line-height: 1.5;
            color: #000;
        }

        .patient-line:last-child {
            margin-bottom: 0;
        }

        .patient-line strong {
            color: #000;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 14px;
        }

        .product-table th {
            color: #000;
            padding: 8px 6px;
            text-align: left;
            border: 1px solid #000;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .product-table td {
            padding: 9px 6px;
            border: 1px solid #000;
            font-size: 10px;
            color: #000;
            background-color: #fff;
        }

        .section-title {
            font-size: 11px;
            color: #000;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .indication-box {
            margin-top: 8px;
            padding: 12px;
            min-height: 88px;
            font-size: 10px;
            background-color: #f8f9fa;
            border: 1px solid #d0d0d0;
            border-radius: 4px;
            color: #000;
        }

        .signature-section {
            margin-top: 28px;
            text-align: center;
        }

        .signature-line {
            border-top: 2px solid #2c5aa0;
            width: 220px;
            margin: 0 auto;
            padding-top: 6px;
            font-size: 10px;
            text-align: center;
            color: #000;
        }
    </style>
</head>
<body>
    @php
        $contact = $productRecipe->contact;
        $phone = $contact->phone_primary ?? $contact->mobile ?? $contact->phone_secondary ?? $contact->phone;
        $gender = $contact->gender?->label();
        $identifier = $contact->identification_number ?? str_pad((string) $contact->id, 4, '0', STR_PAD_LEFT);
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 46%; vertical-align: top;">
                <div class="company-name">
                    {{ $company['company_name'] ?? 'Recetario de productos' }}
                </div>
                <div class="contact-info">
                    {{ $productRecipe->workspace->address }}
                </div>
                <div class="contact-info">
                    {{ $productRecipe->workspace->phone }}
                </div>
            </td>

            <td class="logo-cell" style="width: 20%; vertical-align: middle;">
                @if(isset($company['logo']) && !empty($company['logo']))
                    <img src="{{ storage_path('app/public/' . $company['logo']) }}" alt="Logo" style="max-height: 70px; max-width: 130px; display: block; margin: 0 auto;">
                @endif
            </td>

            <td style="width: 34%; vertical-align: top; text-align: right;">
                <div class="meta-box">
                    <strong>Receta N.:</strong> {{ str_pad((string) $productRecipe->id, 5, '0', STR_PAD_LEFT) }}<br>
                    <strong>Fecha:</strong> {{ $productRecipe->created_at->format('d/m/Y') }}<br>
                    <strong>Sucursal:</strong> {{ $productRecipe->workspace->name }}<br>
                    <strong>Evaluado por:</strong> {{ $productRecipe->optometrist->name }}
                </div>
            </td>
        </tr>
    </table>

    <div class="title">
        Recetario de Productos
    </div>

    <div class="patient-info">
        <div class="patient-line">
            <strong>Contacto:</strong> {{ strtoupper($contact->name) }}
            &nbsp;&nbsp;&nbsp;&nbsp;
            <strong>ID:</strong> {{ $identifier }}
        </div>
        <div class="patient-line">
            <strong>Edad:</strong> {{ $contact->age ?? '-' }}
            &nbsp;&nbsp;&nbsp;&nbsp;
            <strong>Genero:</strong> {{ $gender ?? '-' }}
            &nbsp;&nbsp;&nbsp;&nbsp;
            <strong>Telefono:</strong> {{ $phone ?? '-' }}
        </div>
    </div>

    <table class="product-table">
        <thead>
            <tr>
                <th style="width: 32%;">Producto</th>
                <th style="width: 28%;">Evaluador</th>
                <th style="width: 20%;">Sucursal</th>
                <th style="width: 20%;">Fecha</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $productRecipe->product->name }}</strong></td>
                <td>{{ $productRecipe->optometrist->name }}</td>
                <td>{{ $productRecipe->workspace->name }}</td>
                <td>{{ $productRecipe->created_at->format('d/m/Y') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Indicacion</div>
    <div class="indication-box">
        @if($productRecipe->indication)
            {{ $productRecipe->indication }}
        @else
            Sin indicacion adicional.
        @endif
    </div>

    <div class="signature-section">
        <div class="signature-line">
            FIRMA PROFESIONAL
        </div>
    </div>
</body>
</html>
