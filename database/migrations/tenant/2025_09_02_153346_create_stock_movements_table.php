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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer', 'initial']);
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable(); // quantity * unit_cost
            $table->foreignId('related_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('from_workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            $table->foreignId('to_workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            $table->string('reference_number')->nullable();

            $table->timestamps();

            $table->index(['from_workspace_id', 'to_workspace_id']);
            $table->index(['workspace_id', 'product_id']);
            $table->index(['workspace_id', 'type']);
            $table->index(['created_at']);
            $table->index(['related_invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
