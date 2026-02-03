<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->decimal('discount_amount', 15, 2)->default(0)->change();
        });

        Schema::table('quotation_items', function (Blueprint $table): void {
            $table->decimal('discount_amount', 15, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->decimal('discount_amount', 5, 2)->default(0)->change();
        });

        Schema::table('quotation_items', function (Blueprint $table): void {
            $table->decimal('discount_amount', 5, 2)->default(0)->change();
        });
    }
};
