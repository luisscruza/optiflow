<?php

declare(strict_types=1);

use App\Enums\ContactImportStatus;
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
        Schema::create('contact_imports', function (Blueprint $table): void {
            $table->id();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->json('source_files')->nullable();
            $table->string('status')->default(ContactImportStatus::Pending->value);
            $table->json('headers')->nullable();
            $table->json('column_mapping')->nullable();
            $table->json('import_data')->nullable();
            $table->json('validation_errors')->nullable();
            $table->json('import_summary')->nullable();
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
