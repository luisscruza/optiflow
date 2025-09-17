<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained()->onDelete('cascade');
            $table->decimal('rate', 10, 4); // Rate against the default currency
            $table->datetime('effective_date'); // Date and time when this rate becomes effective
            $table->timestamps();

            // Ensure only one rate per currency per datetime
            $table->unique(['currency_id', 'effective_date']);
            $table->index(['currency_id', 'effective_date']);
        });

        // Seed initial USD rate
        $usdCurrency = DB::table('currencies')->insertGetId([
            'name' => 'United States Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'is_default' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add initial rate for USD (1 USD = 60.00 DOP as shown in screenshot)
        DB::table('currency_rates')->insert([
            'currency_id' => $usdCurrency,
            'rate' => 60.00,
            'effective_date' => '2025-06-25',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
