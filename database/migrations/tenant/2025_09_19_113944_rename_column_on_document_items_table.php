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
            $table->renameColumn('discount', 'discount_amount');
            $table->renameColumn('tax_rate_snapshot', 'tax_rate');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->renameColumn('discount', 'discount_amount');
            $table->renameColumn('tax_rate_snapshot', 'tax_rate');
        });
    }
};
