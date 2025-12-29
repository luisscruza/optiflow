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
        Schema::table('workflows', function (Blueprint $table) {
            // null = not shown, 'optional' = shown but optional, 'required' = shown and required
            $table->string('invoice_requirement')->nullable()->after('is_active');
            $table->string('prescription_requirement')->nullable()->after('invoice_requirement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropColumn(['invoice_requirement', 'prescription_requirement']);
        });
    }
};
