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
        Schema::create('invoice_item_tax', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained()->cascadeOnDelete();
            $table->decimal('rate', 8, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(['invoice_item_id', 'tax_id']);
        });
    }
};
