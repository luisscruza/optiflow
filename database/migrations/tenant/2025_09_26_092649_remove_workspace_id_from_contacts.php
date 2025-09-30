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
        // First, let's check what indexes exist and drop any that reference workspace_id
        $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='contacts' AND sql LIKE '%workspace_id%'");

        foreach ($indexes as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index->name}");
        }

        Schema::table('contacts', function (Blueprint $table) {
            // Drop foreign key constraint if it exists
            try {
                $table->dropForeign(['workspace_id']);
            } catch (Exception $e) {
                // Foreign key might not exist, continue
            }

            // Drop the column
            $table->dropColumn('workspace_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id')->after('id');

            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->onDelete('cascade');

            $table->index(['workspace_id', 'contact_type'], 'contacts_workspace_id_contact_type_index');
        });
    }
};
