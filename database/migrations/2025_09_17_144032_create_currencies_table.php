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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Dominican Peso"
            $table->string('code', 3)->unique(); // e.g., "DOP"
            $table->string('symbol'); // e.g., "RD$"
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure only one default currency
            $table->index(['is_default', 'is_active']);
        });

        // Seed default currency (DOP)
        DB::table('currencies')->insert([
            'name' => 'Dominican Peso',
            'code' => 'DOP',
            'symbol' => 'RD$',
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
