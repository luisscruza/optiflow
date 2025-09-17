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
        Schema::table('documents', function (Blueprint $table) {
            $table->float('tax_amount', 12, 2)->default(0)->after('total_amount');
            $table->float('discount_amount', 12, 2)->default(0)->after('tax_amount');
            $table->float('subtotal_amount', 12, 2)->default(0)->after('discount_amount');
        });
    }
};
