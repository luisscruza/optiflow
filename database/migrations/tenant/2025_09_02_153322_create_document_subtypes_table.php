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
        Schema::create('document_subtypes', function (Blueprint $table): void {
            $table->id();
            $table->string('name'); // e.g Crédito fiscal (01)
            $table->string('sequence')->default('1'); // For numbering documents
            $table->timestamps();

            $table->index(['name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_subtypes');
    }
};
