<?php

declare(strict_types=1);

use App\Actions\SwitchWorkspaceAction;
use App\Models\User;
use App\Models\Workspace;

it('switches to workspace successfully', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->addUser($user, 'member');

    $action = new SwitchWorkspaceAction();
    $result = $action->handle($user, $workspace);

    expect($result)->toBeTrue();
    expect($user->fresh()->current_workspace_id)->toBe($workspace->id);
});

it('fails to switch when user has no access', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $action = new SwitchWorkspaceAction();
    $result = $action->handle($user, $workspace);

    expect($result)->toBeFalse();
    expect($user->fresh()->current_workspace_id)->toBeNull();
});
