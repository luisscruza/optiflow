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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('primary'); // primary, secondary, billing, shipping
            $table->string('province')->nullable();
            $table->string('municipality')->nullable();
            $table->string('country')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['contact_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
