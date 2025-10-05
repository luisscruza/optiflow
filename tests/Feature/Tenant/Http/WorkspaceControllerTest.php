<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Workspace;

it('can view workspace index', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('workspaces.index'));

    $response->assertOk();
});

it('can create workspace', function () {
    $user = User::factory()->hasWorkspaces(1)->create();

    $response = $this->actingAs($user)->post(route('workspaces.store'), [
        'name' => 'Test Workspace',
        'description' => 'Test description',
        'code' => 'TESTCODE',
    ]);

    $response->assertRedirect(route('workspaces.index'));

    expect(Workspace::where('name', 'Test Workspace')->exists())->toBeTrue();
});

it('can update workspace as owner', function () {
    $user = User::factory()->create([
        'business_role' => UserRole::Owner,
    ]);

    $workspace = Workspace::factory()->create();

    $response = $this->actingAs($user)->put(route('workspaces.update', $workspace), [
        'name' => 'Updated Workspace',
        'code' => 'UPDATEDCODE',
    ]);

    $response->assertRedirect();
    expect($workspace->fresh()->name)->toBe('Updated Workspace');
});

it('cannot update workspace as non-owner', function ($role) {
    $user = User::factory()->create([
        'business_role' => $role,
    ]);

    $workspace = Workspace::factory()->create();

    $response = $this->actingAs($user)->put(route('workspaces.update', $workspace), [
        'name' => 'Updated Workspace',
        'code' => 'UPDATEDCODE',
    ]);

    $response->assertStatus(403);
    expect($workspace->fresh()->name)->not->toBe('Updated Workspace');
})->with([
    [UserRole::Admin],
    [UserRole::Sales],
    [UserRole::Support],
    [UserRole::Marketing],
    [UserRole::User],
]);
