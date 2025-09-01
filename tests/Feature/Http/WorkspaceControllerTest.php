<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;

it('can view workspace index', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('workspaces.index'));

    $response->assertOk();
});

it('can create workspace', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('workspaces.store'), [
        'name' => 'Test Workspace',
        'description' => 'Test description',
    ]);

    $response->assertRedirect(route('workspaces.index'));
    expect(Workspace::where('name', 'Test Workspace')->exists())->toBeTrue();
});

it('can update workspace as owner', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');

    $response = $this->actingAs($user)->put(route('workspaces.update', $workspace), [
        'name' => 'Updated Workspace',
    ]);

    $response->assertRedirect();
    expect($workspace->fresh()->name)->toBe('Updated Workspace');
});

it('can delete workspace as owner', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('workspaces.destroy', $workspace));

    $response->assertRedirect();
    expect(Workspace::find($workspace->id))->toBeNull();
});
