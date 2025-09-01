<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;

it('has workspace relationships', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');

    expect($user->workspaces()->count())->toBe(1);
    expect($user->ownedWorkspaces()->count())->toBe(1);
});

it('can switch to workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->addUser($user, 'member');

    $result = $user->switchToWorkspace($workspace);

    expect($result)->toBeTrue();
    expect($user->current_workspace_id)->toBe($workspace->id);
});

it('cannot switch to workspace without access', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $result = $user->switchToWorkspace($workspace);

    expect($result)->toBeFalse();
});

it('can check workspace access', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    expect($user->hasAccessToWorkspace($workspace))->toBeFalse();

    $workspace->addUser($user, 'member');

    expect($user->hasAccessToWorkspace($workspace))->toBeTrue();
});
