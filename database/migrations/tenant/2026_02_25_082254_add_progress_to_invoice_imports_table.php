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
        Schema::table('invoice_imports', function (Blueprint $table): void {
            $table->unsignedInteger('total_records')->default(0)->after('offset');
            $table->unsignedInteger('processed_records')->default(0)->after('total_records');
            $table->unsignedInteger('imported_records')->default(0)->after('processed_records');
            $table->unsignedInteger('skipped_records')->default(0)->after('imported_records');
            $table->unsignedInteger('error_records')->default(0)->after('skipped_records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_imports', function (Blueprint $table): void {
            $table->dropColumn([
                'total_records',
                'processed_records',
                'imported_records',
                'skipped_records',
                'error_records',
            ]);
        });
    }
};
