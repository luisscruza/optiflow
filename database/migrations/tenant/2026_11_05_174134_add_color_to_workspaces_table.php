<?php

declare(strict_types=1);

use App\Models\Workspace;
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
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->string('color', 7)->nullable()->after('is_default');
        });

        DB::table('workspaces')->orderBy('id')->pluck('id')->each(function (int $id): void {
            DB::table('workspaces')->where('id', $id)->update(['color' => Workspace::randomColor()]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->dropColumn('color');
        });
    }
};
