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
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_optometrist_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('closed_by_user_id');
            $table->dropConstrainedForeignId('closed_by_optometrist_id');
            $table->dropColumn('closed_at');
        });
    }
};
