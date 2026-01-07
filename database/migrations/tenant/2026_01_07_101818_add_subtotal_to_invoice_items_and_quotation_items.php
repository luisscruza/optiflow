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
        Schema::table('invoice_items', function (Blueprint $table) {
            // Add subtotal column (raw: quantity * unit_price, before discount and tax)
            $table->decimal('subtotal', 15, 2)->default(0)->after('unit_price');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            // Add subtotal column (raw: quantity * unit_price, before discount and tax)
            $table->decimal('subtotal', 15, 2)->default(0)->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('subtotal');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('subtotal');
        });
    }
};
