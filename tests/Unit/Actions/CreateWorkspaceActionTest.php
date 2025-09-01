<?php

declare(strict_types=1);

use App\Actions\CreateWorkspaceAction;
use App\Models\User;
use App\Models\Workspace;

it('creates a workspace successfully', function () {
    $user = User::factory()->create();
    $action = new CreateWorkspaceAction();

    $validated = [
        'name' => 'Test Workspace',
        'description' => 'A test workspace',
    ];

    $workspace = $action->handle($user, $validated);

    expect($workspace)->toBeInstanceOf(Workspace::class);
    expect($workspace->name)->toBe('Test Workspace');
    expect($workspace->slug)->toBe('test-workspace');
    expect($workspace->description)->toBe('A test workspace');
    expect($workspace->owner_id)->toBe($user->id);
    expect($workspace->is_active)->toBeTrue();

    // Check user is added as owner
    expect($workspace->hasUser($user))->toBeTrue();
    expect($workspace->users()->where('user_id', $user->id)->first()->pivot->role)->toBe('owner');

    expect($user->fresh()->current_workspace_id)->toBe($workspace->id);
});

it('creates a workspace without description', function () {
    $user = User::factory()->create();
    $action = new CreateWorkspaceAction();

    $validated = [
        'name' => 'Test Workspace',
    ];

    $workspace = $action->handle($user, $validated);

    expect($workspace->description)->toBeNull();
});
