<?php

declare(strict_types=1);

use App\Enums\UserRole;
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
        // Add business_role to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('business_role')->default(UserRole::Sales->value)->after('email_verified_at');
            $table->index('business_role');
        });

        // Keep workspace roles in user_workspace pivot table (no changes needed)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove business_role from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['business_role']);
            $table->dropColumn('business_role');
        });

        // Workspace roles remain unchanged
    }
};
