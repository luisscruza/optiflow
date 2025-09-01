<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;

it('can switch to workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->addUser($user, 'member');

    $response = $this->actingAs($user)->patch(route('workspace-context.update', $workspace));

    $response->assertRedirect();
    expect($user->fresh()->current_workspace_id)->toBe($workspace->id);
});

it('can leave workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->addUser($user, 'member');

    $response = $this->actingAs($user)->delete(route('workspace-context.destroy', $workspace));

    $response->assertRedirect();
    expect($workspace->fresh()->hasUser($user))->toBeFalse();
});
