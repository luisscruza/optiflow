<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\PermissionFactory;

test('permission search keeps the typed query in the role dialog', function (): void {
    pest()->browser()->withHost('pest.opticanet.test');

    $user = User::factory()->createOne([
        'business_role' => UserRole::Owner,
        'password_changed_at' => now(),
    ]);

    $workspace = Workspace::factory()->createOne([
        'owner_id' => $user->id,
    ]);

    $user->workspaces()->attach($workspace, [
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $user->update([
        'current_workspace_id' => $workspace->id,
    ]);

    /** @var Illuminate\Contracts\Auth\Authenticatable $user */
    PermissionFactory::new()->create([
        'name' => Permission::ViewHistoryLogs->value,
        'guard_name' => 'web',
    ]);

    PermissionFactory::new()->create([
        'name' => Permission::ContactsView->value,
        'guard_name' => 'web',
    ]);

    $this->actingAs($user);

    visit('/business/roles')
        ->assertSee('Roles y permisos')
        ->click('Crear rol')
        ->type('input#create-name', 'Auditor')
        ->typeSlowly('input[placeholder="Buscar permisos..."]', 'historial')
        ->assertValue('input[placeholder="Buscar permisos..."]', 'historial')
        ->assertSee('Ver historial de cambios')
        ->assertDontSee('Ver contactos')
        ->assertNoJavascriptErrors();
});
