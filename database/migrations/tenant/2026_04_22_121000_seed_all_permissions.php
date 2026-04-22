<?php

declare(strict_types=1);

use App\Enums\Permission as EnumsPermission;
use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = EnumsPermission::cases();

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
