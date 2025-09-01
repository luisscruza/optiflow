<?php

declare(strict_types=1);

use App\Actions\DeleteWorkspaceAction;
use App\Models\User;
use App\Models\Workspace;

it('deletes workspace successfully as owner', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);

    $action = new DeleteWorkspaceAction();
    $action->handle($user, $workspace);

    expect(Workspace::find($workspace->id))->toBeNull();
});

it('throws exception when user is not owner', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $action = new DeleteWorkspaceAction();
    $action->handle($user, $workspace);
})->throws('Symfony\Component\HttpKernel\Exception\HttpException');
