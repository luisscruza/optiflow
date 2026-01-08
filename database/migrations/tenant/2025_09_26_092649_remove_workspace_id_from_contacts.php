<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            // Drop foreign key constraint first
            $table->dropForeign(['workspace_id']);

            // Drop indexes that include workspace_id
            $table->dropIndex(['workspace_id', 'contact_type']);
            $table->dropIndex(['workspace_id', 'name']);

            // Drop the column
            $table->dropColumn('workspace_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->unsignedBigInteger('workspace_id')->after('id');

            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->onDelete('cascade');

            $table->index(['workspace_id', 'contact_type'], 'contacts_workspace_id_contact_type_index');
        });
    }
};
