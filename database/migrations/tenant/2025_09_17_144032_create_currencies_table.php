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
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('name'); // e.g., "Dominican Peso"
            $table->string('code')->unique(); // e.g., "DOP"
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

        // Add foreign key constraint to invoices table
        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
        });

        // Add foreign key constraint to quotations table
        Schema::table('quotations', function (Blueprint $table): void {
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropForeign(['currency_id']);
        });

        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropForeign(['currency_id']);
        });

        Schema::dropIfExists('currencies');
    }
};
