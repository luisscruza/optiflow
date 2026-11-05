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
        if (! Schema::hasTable('prescription_closures')) {
            Schema::create('prescription_closures', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('prescription_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('optometrist_id')->nullable()->constrained('contacts')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamp('closed_at');
                $table->timestamps();
            });
        }

        // Tenants where the original column-based migration succeeded keep their data.
        if (! Schema::hasColumn('prescriptions', 'closed_at')) {
            return;
        }

        DB::table('prescriptions')
            ->whereNotNull('closed_at')
            ->orderBy('id')
            ->get(['id', 'closed_at', 'closed_by_optometrist_id', 'closed_by_user_id'])
            ->each(function (object $prescription): void {
                DB::table('prescription_closures')->insertOrIgnore([
                    'prescription_id' => $prescription->id,
                    'optometrist_id' => $prescription->closed_by_optometrist_id,
                    'user_id' => $prescription->closed_by_user_id,
                    'closed_at' => $prescription->closed_at,
                    'created_at' => $prescription->closed_at,
                    'updated_at' => $prescription->closed_at,
                ]);
            });

        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('closed_by_user_id');
            $table->dropConstrainedForeignId('closed_by_optometrist_id');
            $table->dropColumn('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_closures');
    }
};
