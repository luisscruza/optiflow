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
        Schema::table('product_stocks', function (Blueprint $table): void {
            $table->foreignId('supplier_id')->nullable()->after('workspace_id')->constrained('contacts')->nullOnDelete();
            $table->index(['workspace_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table): void {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['workspace_id', 'supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }
};
