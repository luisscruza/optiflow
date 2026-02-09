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
        Schema::dropIfExists('product_inventory_adjustment_items');

        Schema::create('product_inventory_adjustment_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_inventory_adjustment_id')
                ->constrained(table: 'product_inventory_adjustments', indexName: 'pia_items_adjustment_fk')
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('adjustment_type', ['increment', 'decrement']);
            $table->decimal('quantity', 10, 2);
            $table->decimal('current_quantity', 10, 2);
            $table->decimal('final_quantity', 10, 2);
            $table->decimal('average_cost', 10, 2)->default(0);
            $table->decimal('total_adjusted', 12, 2)->default(0);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_inventory_adjustment_items');
    }
};
