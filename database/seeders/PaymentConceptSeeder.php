<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChartAccount;
use App\Models\PaymentConcept;
use Illuminate\Database\Seeder;

final class PaymentConceptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Conceptos de pago comunes para una óptica en República Dominicana.
     */
    public function run(): void
    {
        $concepts = [
            // Ingresos relacionados a ventas
            [
                'code' => 'VTA-MON',
                'name' => 'Venta de Monturas',
                'chart_account_code' => '4.1.01',
                'description' => 'Ingresos por venta de monturas y armazones',
                'is_system' => true,
            ],
            [
                'code' => 'VTA-LOF',
                'name' => 'Venta de Lentes Oftálmicos',
                'chart_account_code' => '4.1.02',
                'description' => 'Ingresos por venta de lentes graduados',
                'is_system' => true,
            ],
            [
                'code' => 'VTA-LCO',
                'name' => 'Venta de Lentes de Contacto',
                'chart_account_code' => '4.1.03',
                'description' => 'Ingresos por venta de lentes de contacto',
                'is_system' => true,
            ],
            [
                'code' => 'VTA-SOL',
                'name' => 'Venta de Lentes de Sol',
                'chart_account_code' => '4.1.04',
                'description' => 'Ingresos por venta de gafas de sol',
                'is_system' => true,
            ],
            [
                'code' => 'SRV-OPT',
                'name' => 'Servicios de Optometría',
                'chart_account_code' => '4.1.05',
                'description' => 'Ingresos por consultas y exámenes visuales',
                'is_system' => true,
            ],
            [
                'code' => 'SRV-LAB',
                'name' => 'Servicios de Laboratorio',
                'chart_account_code' => '4.1.06',
                'description' => 'Ingresos por servicios de laboratorio óptico',
                'is_system' => true,
            ],
            [
                'code' => 'VTA-ACC',
                'name' => 'Venta de Accesorios',
                'chart_account_code' => '4.1.07',
                'description' => 'Ingresos por venta de estuches, paños, cordones, etc.',
                'is_system' => true,
            ],
            [
                'code' => 'VTA-SOL-LIQ',
                'name' => 'Venta de Soluciones',
                'chart_account_code' => '4.1.08',
                'description' => 'Ingresos por venta de soluciones para lentes de contacto',
                'is_system' => true,
            ],
            [
                'code' => 'SRV-REP',
                'name' => 'Reparaciones y Ajustes',
                'chart_account_code' => '4.1.09',
                'description' => 'Ingresos por reparación y ajuste de monturas',
                'is_system' => true,
            ],

            // Otros ingresos
            [
                'code' => 'ING-INT',
                'name' => 'Ingresos por Intereses',
                'chart_account_code' => '4.2.01',
                'description' => 'Intereses ganados en cuentas bancarias o inversiones',
                'is_system' => true,
            ],
            [
                'code' => 'ING-DIF-CAM',
                'name' => 'Ganancia por Diferencial Cambiario',
                'chart_account_code' => '4.2.02',
                'description' => 'Ganancia por fluctuación en tipo de cambio',
                'is_system' => true,
            ],
            [
                'code' => 'DEV-IMP',
                'name' => 'Devolución de Impuestos',
                'chart_account_code' => '4.2.03',
                'description' => 'Devolución de ISR, ITBIS u otros impuestos',
                'is_system' => true,
            ],
            [
                'code' => 'ING-DIV',
                'name' => 'Ingresos Diversos',
                'chart_account_code' => '4.2.04',
                'description' => 'Otros ingresos no clasificados',
                'is_system' => true,
            ],
            [
                'code' => 'COM-REC',
                'name' => 'Comisiones Recibidas',
                'chart_account_code' => '4.2.05',
                'description' => 'Comisiones recibidas por referidos u otros conceptos',
                'is_system' => true,
            ],

            // Anticipos y adelantos
            [
                'code' => 'ANT-CLI',
                'name' => 'Anticipo de Cliente',
                'chart_account_code' => '2.1.1.03',
                'description' => 'Anticipo recibido de cliente a cuenta de pedido',
                'is_system' => true,
            ],
            [
                'code' => 'DEP-GAR',
                'name' => 'Depósito en Garantía',
                'chart_account_code' => '2.1.1.03',
                'description' => 'Depósito recibido como garantía',
                'is_system' => true,
            ],

            // Recuperaciones
            [
                'code' => 'REC-CXC',
                'name' => 'Recuperación de Cuentas Incobrables',
                'chart_account_code' => '4.2.04',
                'description' => 'Recuperación de cuentas previamente dadas de baja',
                'is_system' => true,
            ],
            [
                'code' => 'REC-SEG',
                'name' => 'Cobro de Seguro',
                'chart_account_code' => '4.2.04',
                'description' => 'Indemnización recibida de seguro',
                'is_system' => true,
            ],

            // Planes y convenios
            [
                'code' => 'COB-ARS',
                'name' => 'Cobro de ARS',
                'chart_account_code' => '4.2.04',
                'description' => 'Cobro de seguros de salud (ARS)',
                'is_system' => true,
            ],
            [
                'code' => 'COB-CONV',
                'name' => 'Cobro de Convenio Empresarial',
                'chart_account_code' => '4.2.04',
                'description' => 'Cobro por convenio con empresa o institución',
                'is_system' => true,
            ],

            // Venta de activos
            [
                'code' => 'VTA-ACT',
                'name' => 'Venta de Activos',
                'chart_account_code' => '4.2.04',
                'description' => 'Ingresos por venta de equipos u otros activos',
                'is_system' => true,
            ],

            // Préstamos
            [
                'code' => 'ING-PRES',
                'name' => 'Ingreso por Préstamo',
                'chart_account_code' => '2.1.4.01',
                'description' => 'Dinero recibido por préstamo bancario',
                'is_system' => true,
            ],
        ];

        foreach ($concepts as $concept) {
            $chartAccount = ChartAccount::query()
                ->where('code', $concept['chart_account_code'])
                ->first();

            PaymentConcept::query()->updateOrCreate(
                ['code' => $concept['code']],
                [
                    'name' => $concept['name'],
                    'chart_account_id' => $chartAccount?->id,
                    'description' => $concept['description'],
                    'is_active' => true,
                    'is_system' => $concept['is_system'],
                ]
            );
        }
    }
}
