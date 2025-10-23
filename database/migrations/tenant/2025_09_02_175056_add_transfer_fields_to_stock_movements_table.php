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
        Schema::table('stock_movements', function (Blueprint $table): void {
            // Add transfer-specific fields

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropForeign(['from_workspace_id']);
            $table->dropForeign(['to_workspace_id']);
            $table->dropIndex(['from_workspace_id', 'to_workspace_id']);
            $table->dropColumn(['from_workspace_id', 'to_workspace_id', 'reference_number']);
        });
    }
};
