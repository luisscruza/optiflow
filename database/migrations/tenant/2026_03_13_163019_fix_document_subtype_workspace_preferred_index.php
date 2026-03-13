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
        Schema::table('document_subtype_workspace', function (Blueprint $table): void {
            $table->index('workspace_id', 'document_subtype_workspace_workspace_id_idx');
            $table->dropUnique('unique_workspace_preferred');
            $table->unique(['document_subtype_id', 'workspace_id'], 'document_subtype_workspace_unique');
            $table->index(['workspace_id', 'is_preferred'], 'document_subtype_workspace_preferred_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_subtype_workspace', function (Blueprint $table): void {
            $table->dropIndex('document_subtype_workspace_preferred_idx');
            $table->dropUnique('document_subtype_workspace_unique');
            $table->unique(['workspace_id', 'is_preferred'], 'unique_workspace_preferred');
            $table->dropIndex('document_subtype_workspace_workspace_id_idx');
        });
    }
};
