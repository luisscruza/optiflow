<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\Workspace;

it('has workspace relationships', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');

    expect($user->workspaces()->count())->toBe(1);
    expect($user->ownedWorkspaces()->count())->toBe(1);
});

it('can switch to workspace', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->addUser($user, 'member');

    $result = $user->switchToWorkspace($workspace);

    expect($result)->toBeTrue();
    expect($user->current_workspace_id)->toBe($workspace->id);
});

it('cannot switch to workspace without access', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $result = $user->switchToWorkspace($workspace);

    expect($result)->toBeFalse();
});

it('can check workspace access', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    expect($user->hasAccessToWorkspace($workspace))->toBeFalse();

    $workspace->addUser($user, 'member');

    expect($user->hasAccessToWorkspace($workspace))->toBeTrue();
});

test('has many sent invitations', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->sentInvitations()->createMany([
        ['email' => 'test@example.com', 'token' => Str::random(32), 'workspace_id' => $workspace->id, 'role' => UserRole::User, 'expires_at' => now()->addDays(7)],
        ['email' => 'test2@example.com', 'token' => Str::random(32), 'workspace_id' => $workspace->id, 'role' => UserRole::Admin, 'expires_at' => now()->addDays(7)],
    ]);
    expect($user->sentInvitations)->toHaveCount(2);
    expect($user->sentInvitations->first())->toBeInstanceOf(UserInvitation::class);
});

test('has many received invitations', function (): void {
    $user = User::factory()->create();
    $userWhoInvited = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->receivedInvitations()->createMany([
        ['email' => 'test@example.com', 'token' => Str::random(32), 'workspace_id' => $workspace->id, 'role' => UserRole::User, 'expires_at' => now()->addDays(7), 'invited_by' => $userWhoInvited->id],
        ['email' => 'test2@example.com', 'token' => Str::random(32), 'workspace_id' => $workspace->id, 'role' => UserRole::Admin, 'expires_at' => now()->addDays(7), 'invited_by' => $userWhoInvited->id],
    ]);
    expect($user->receivedInvitations)->toHaveCount(2);
    expect($user->receivedInvitations->first())->toBeInstanceOf(UserInvitation::class);
});
