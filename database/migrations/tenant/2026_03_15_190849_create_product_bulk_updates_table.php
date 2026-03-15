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
        Schema::create('product_bulk_updates', function (Blueprint $table): void {
            $table->id();
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('status')->default('pending');
            $table->json('headers')->nullable();
            $table->json('preview_rows')->nullable();
            $table->json('validation_errors')->nullable();
            $table->json('summary')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bulk_updates');
    }
};
