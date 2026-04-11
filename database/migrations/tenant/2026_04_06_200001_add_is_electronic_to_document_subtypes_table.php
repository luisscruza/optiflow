<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_subtypes', function (Blueprint $table): void {
            $table->boolean('is_electronic')->default(false)->after('type');
        });

        // Seed E31 and E32 electronic document subtypes
        DB::table('document_subtypes')->insert([
            [
                'name' => 'Factura de Crédito Fiscal Electrónica',
                'prefix' => 'E31',
                'type' => 'invoice',
                'is_electronic' => true,
                'is_default' => false,
                'start_number' => 1,
                'end_number' => 99999999,
                'next_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Factura de Consumo Electrónica',
                'prefix' => 'E32',
                'type' => 'invoice',
                'is_electronic' => true,
                'is_default' => false,
                'start_number' => 1,
                'end_number' => 99999999,
                'next_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('document_subtypes')->whereIn('prefix', ['E31', 'E32'])->delete();

        Schema::table('document_subtypes', function (Blueprint $table): void {
            $table->dropColumn('is_electronic');
        });
    }
};
