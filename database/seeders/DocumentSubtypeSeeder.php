<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DocumentSubtype;
use Illuminate\Database\Seeder;

final class DocumentSubtypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ncfTypes = [
            [
                'name' => 'Factura de Crédito Fiscal',
                'prefix' => 'B01',
                'start_number' => 1,
                'end_number' => 50000000,
                'next_number' => 1,
                'valid_until_date' => now()->addYear(),
                'is_default' => true,
            ],
            [
                'name' => 'Factura de Consumo',
                'prefix' => 'B02',
                'start_number' => 1,
                'end_number' => 50000000,
                'next_number' => 1,
                'valid_until_date' => now()->addYear(),
                'is_default' => false,
            ],
            [
                'name' => 'Nota de Débito',
                'prefix' => 'B03',
                'start_number' => 1,
                'end_number' => 50000000,
                'next_number' => 1,
                'valid_until_date' => now()->addYear(),
                'is_default' => false,
            ],
            [
                'name' => 'Nota de Crédito',
                'prefix' => 'B04',
                'start_number' => 1,
                'end_number' => 50000000,
                'next_number' => 1,
                'valid_until_date' => now()->addYear(),
                'is_default' => false,
            ],
            [
                'name' => 'Comprobante de Compras',
                'prefix' => 'B11',
                'start_number' => 1,
                'end_number' => 50000000,
                'next_number' => 1,
                'valid_until_date' => now()->addYear(),
                'is_default' => false,
            ],
            [
                'name' => 'Registro Único de Ingresos',
                'prefix' => 'B12',
                'start_number' => 1,
                'end_number' => 50000000,
                'next_number' => 1,
                'valid_until_date' => now()->addYear(),
                'is_default' => false,
            ],
            [
                'name' => 'Comprobante para Gastos Menores',
                'prefix' => 'B13',
                'start_number' => 1,
                'end_number' => 50000000,
                'next_number' => 1,
                'valid_until_date' => now()->addYear(),
                'is_default' => false,
            ],
            [
                'name' => 'Comprobante de Regímenes Especiales',
                'prefix' => 'B14',
                'start_number' => 1,
                'end_number' => 50000000,
                'next_number' => 1,
                'valid_until_date' => now()->addYear(),
                'is_default' => false,
            ],
            [
                'name' => 'Comprobante Gubernamental',
                'prefix' => 'B15',
                'start_number' => 1,
                'end_number' => 50000000,
                'next_number' => 1,
                'valid_until_date' => now()->addYear(),
                'is_default' => false,
            ],
            [
                'name' => 'Comprobante para Exportaciones',
                'prefix' => 'B16',
                'start_number' => 1,
                'end_number' => 50000000,
                'next_number' => 1,
                'valid_until_date' => now()->addYear(),
                'is_default' => false,
            ],
            [
                'name' => 'Comprobante para Pagos al Exterior',
                'prefix' => 'B17',
                'start_number' => 1,
                'end_number' => 50000000,
                'next_number' => 1,
                'valid_until_date' => now()->addYear(),
                'is_default' => false,
            ],
            [
                'name' => 'Factura Electrónica de Crédito Fiscal',
                'prefix' => 'E01',
                'start_number' => 1,
                'end_number' => null,
                'next_number' => 1,
                'valid_until_date' => null,
                'is_default' => false,
            ],
            [
                'name' => 'Factura Electrónica de Consumo',
                'prefix' => 'E02',
                'start_number' => 1,
                'end_number' => null,
                'next_number' => 1,
                'valid_until_date' => null,
                'is_default' => false,
            ],
            [
                'name' => 'Nota de Débito Electrónica',
                'prefix' => 'E03',
                'start_number' => 1,
                'end_number' => null,
                'next_number' => 1,
                'valid_until_date' => null,
                'is_default' => false,
            ],
            [
                'name' => 'Nota de Crédito Electrónica',
                'prefix' => 'E04',
                'start_number' => 1,
                'end_number' => null,
                'next_number' => 1,
                'valid_until_date' => null,
                'is_default' => false,
            ],
            [
                'name' => 'Comprobante de Compras Electrónico',
                'prefix' => 'E11',
                'start_number' => 1,
                'end_number' => null,
                'next_number' => 1,
                'valid_until_date' => null,
                'is_default' => false,
            ],
            [
                'name' => 'Registro Único de Ingresos Electrónico',
                'prefix' => 'E12',
                'start_number' => 1,
                'end_number' => null,
                'next_number' => 1,
                'valid_until_date' => null,
                'is_default' => false,
            ],
        ];

        foreach ($ncfTypes as $ncfType) {
            DocumentSubtype::firstOrCreate(
                ['prefix' => $ncfType['prefix']],
                $ncfType
            );
        }
    }
}
