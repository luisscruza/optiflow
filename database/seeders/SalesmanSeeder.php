<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Salesman;
use Illuminate\Database\Seeder;

final class SalesmanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some example salesmen
        Salesman::factory()->count(5)->create();
    }
}
