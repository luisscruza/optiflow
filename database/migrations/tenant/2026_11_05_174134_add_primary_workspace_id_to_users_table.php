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
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('primary_workspace_id')
                ->nullable()
                ->after('current_workspace_id')
                ->constrained('workspaces')
                ->nullOnDelete();
        });

        // Users that belong to a single branch unambiguously work there.
        DB::table('user_workspace')
            ->select('user_id', DB::raw('MIN(workspace_id) as workspace_id'))
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) = 1')
            ->get()
            ->each(function (object $membership): void {
                DB::table('users')
                    ->where('id', $membership->user_id)
                    ->update(['primary_workspace_id' => $membership->workspace_id]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('primary_workspace_id');
        });
    }
};
