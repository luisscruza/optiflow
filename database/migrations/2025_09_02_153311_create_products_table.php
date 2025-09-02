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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // Selling price
            $table->decimal('cost', 10, 2)->nullable(); // Cost price
            $table->boolean('track_stock')->default(true);
            $table->foreignId('default_tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->timestamps();

            $table->index(['name']);
            $table->index(['sku']);
            $table->index(['track_stock']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
