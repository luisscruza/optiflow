<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChartAccount;
use App\Models\WithholdingType;
use Illuminate\Database\Seeder;

final class WithholdingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Tipos de retenciones comunes en República Dominicana según la DGII.
     */
    public function run(): void
    {
        $withholdingTypes = [
            // Retenciones de ISR
            [
                'code' => 'RET-ISR-27',
                'name' => 'Retención ISR 27%',
                'percentage' => 27.00,
                'chart_account_code' => '2.1.3.03',
                'description' => 'Retención del Impuesto Sobre la Renta 27% - Persona Jurídica',
            ],
            [
                'code' => 'RET-ISR-10',
                'name' => 'Retención ISR 10%',
                'percentage' => 10.00,
                'chart_account_code' => '2.1.3.03',
                'description' => 'Retención del Impuesto Sobre la Renta 10% - Honorarios Profesionales',
            ],
            [
                'code' => 'RET-ISR-15',
                'name' => 'Retención ISR 15%',
                'percentage' => 15.00,
                'chart_account_code' => '2.1.3.03',
                'description' => 'Retención del Impuesto Sobre la Renta 15% - Premios y Alquileres',
            ],
            [
                'code' => 'RET-ISR-25',
                'name' => 'Retención ISR 25%',
                'percentage' => 25.00,
                'chart_account_code' => '2.1.3.03',
                'description' => 'Retención del Impuesto Sobre la Renta 25% - Pagos al Exterior',
            ],
            [
                'code' => 'RET-ISR-5',
                'name' => 'Retención ISR 5%',
                'percentage' => 5.00,
                'chart_account_code' => '2.1.3.03',
                'description' => 'Retención del Impuesto Sobre la Renta 5% - Intereses',
            ],
            [
                'code' => 'RET-ISR-2',
                'name' => 'Retención ISR 2%',
                'percentage' => 2.00,
                'chart_account_code' => '2.1.3.03',
                'description' => 'Retención del Impuesto Sobre la Renta 2% - Compras a Personas Físicas',
            ],

            // Retenciones de ITBIS
            [
                'code' => 'RET-ITBIS-100',
                'name' => 'Retención ITBIS 100%',
                'percentage' => 18.00,
                'chart_account_code' => '2.1.3.04',
                'description' => 'Retención del 100% del ITBIS (18%)',
            ],
            [
                'code' => 'RET-ITBIS-30',
                'name' => 'Retención ITBIS 30%',
                'percentage' => 5.40,
                'chart_account_code' => '2.1.3.04',
                'description' => 'Retención del 30% del ITBIS (5.4%)',
            ],
            [
                'code' => 'RET-ITBIS-75',
                'name' => 'Retención ITBIS 75%',
                'percentage' => 13.50,
                'chart_account_code' => '2.1.3.04',
                'description' => 'Retención del 75% del ITBIS (13.5%)',
            ],

            // Otras retenciones
            [
                'code' => 'RET-AFP',
                'name' => 'Retención AFP',
                'percentage' => 2.87,
                'chart_account_code' => '2.1.2.05',
                'description' => 'Retención para fondo de pensiones (empleado)',
            ],
            [
                'code' => 'RET-ARS',
                'name' => 'Retención SFS/ARS',
                'percentage' => 3.04,
                'chart_account_code' => '2.1.2.06',
                'description' => 'Retención para seguro familiar de salud (empleado)',
            ],
        ];

        foreach ($withholdingTypes as $type) {
            $chartAccount = ChartAccount::query()
                ->where('code', $type['chart_account_code'])
                ->first();

            WithholdingType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'percentage' => $type['percentage'],
                    'chart_account_id' => $chartAccount?->id,
                    'description' => $type['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
