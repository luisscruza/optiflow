<?php

declare(strict_types=1);

use App\Actions\UpdateWorkspaceAction;
use App\Models\User;
use App\Models\Workspace;

it('updates workspace successfully as owner', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');

    $action = new UpdateWorkspaceAction();
    $validated = [
        'name' => 'Updated Workspace',
        'description' => 'Updated description',
    ];

    $result = $action->handle($user, $workspace, $validated);

    expect($result->name)->toBe('Updated Workspace');
    expect($result->slug)->toBe('updated-workspace');
    expect($result->description)->toBe('Updated description');
});

it('updates workspace successfully as admin', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->addUser($user, 'admin');

    $action = new UpdateWorkspaceAction();
    $validated = ['name' => 'Updated Workspace'];

    $result = $action->handle($user, $workspace, $validated);

    expect($result->name)->toBe('Updated Workspace');
});

it('throws exception when user is not owner or admin', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->addUser($user, 'member');

    $action = new UpdateWorkspaceAction();
    $validated = ['name' => 'Updated Workspace'];

    $action->handle($user, $workspace, $validated);
})->throws('Symfony\Component\HttpKernel\Exception\HttpException');
