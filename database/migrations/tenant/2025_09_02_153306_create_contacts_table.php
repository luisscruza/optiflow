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
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone_primary')->nullable();
            $table->string('phone_secondary')->nullable();
            $table->string('mobile')->nullable();
            $table->string('fax')->nullable();
            $table->string('identification_type')->nullable();
            $table->string('identification_number')->nullable();
            $table->string('contact_type');
            $table->string('status')->default('active');
            $table->text('observations')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->index(['identification_type', 'identification_number']);
            $table->index('status');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'contact_type']);
            $table->index(['workspace_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
