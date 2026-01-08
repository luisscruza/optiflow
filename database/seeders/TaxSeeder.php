<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

final class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            // ITBIS (Impuesto a la Transferencia de Bienes Industrializados y Servicios)
            ['name' => 'ITBIS 18%', 'rate' => 18.00, 'is_default' => true, 'type' => 'itbis'],
            ['name' => 'ITBIS 16%', 'rate' => 16.00, 'is_default' => false, 'type' => 'itbis'],
            ['name' => 'ITBIS 13%', 'rate' => 13.00, 'is_default' => false, 'type' => 'itbis'],
            ['name' => 'ITBIS 11%', 'rate' => 11.00, 'is_default' => false, 'type' => 'itbis'],
            ['name' => 'ITBIS 8%', 'rate' => 8.00, 'is_default' => false, 'type' => 'itbis'],
            ['name' => 'ITBIS 0%', 'rate' => 0.00, 'is_default' => false, 'type' => 'itbis'],

            // ISC (Impuesto Selectivo al Consumo)
            ['name' => 'ISC 10%', 'rate' => 10.00, 'is_default' => false, 'type' => 'isc'],
            ['name' => 'ISC 15%', 'rate' => 15.00, 'is_default' => false, 'type' => 'isc'],
            ['name' => 'ISC 20%', 'rate' => 20.00, 'is_default' => false, 'type' => 'isc'],
            ['name' => 'ISC 25%', 'rate' => 25.00, 'is_default' => false, 'type' => 'isc'],

            // Propina Legal
            ['name' => 'Propina Legal 10%', 'rate' => 10.00, 'is_default' => false, 'type' => 'propina_legal'],

            // Exento
            ['name' => 'Exento', 'rate' => 0.00, 'is_default' => false, 'type' => 'exento'],

            // No Facturable
            ['name' => 'No Facturable', 'rate' => 0.00, 'is_default' => false, 'type' => 'no_facturable'],
        ];

        foreach ($taxes as $tax) {
            Tax::query()->create($tax);
        }
    }
}
