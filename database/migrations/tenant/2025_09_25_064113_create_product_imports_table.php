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
        Schema::create('product_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('status')->default('pending'); // pending, mapping, processing, completed, failed
            $table->json('headers')->nullable(); // Store the column headers from the uploaded file
            $table->json('column_mapping')->nullable(); // Store the column mapping (excel_column => product_field)
            $table->json('import_data')->nullable(); // Store the raw imported data temporarily
            $table->json('validation_errors')->nullable(); // Store validation errors
            $table->json('import_summary')->nullable(); // Store import results (imported, skipped, errors)
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('error_rows')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['created_at']);
        });
    }
};
