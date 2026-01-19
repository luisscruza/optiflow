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
        if (! Schema::hasTable('document_subtype_workspace')) {
            Schema::create('document_subtype_workspace', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('document_subtype_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_preferred')->default(false);
                $table->timestamps();

                $table->unique(['workspace_id', 'is_preferred'], 'unique_workspace_preferred');
                $table->index(['document_subtype_id', 'workspace_id'], 'idx_document_subtype_workspace');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_subtype_workspace');
    }
};
