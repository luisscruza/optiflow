<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;

it('has user relationships', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create();
    $workspace->addUser($user, 'member');

    expect($workspace->users()->count())->toBe(1);
    expect($workspace->hasUser($user))->toBeTrue();
});

it('can add and remove users', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create();

    $workspace->addUser($user, 'admin');
    expect($workspace->hasUser($user))->toBeTrue();

    $workspace->removeUser($user);
    expect($workspace->hasUser($user))->toBeFalse();
});

it('generates slug from name', function (): void {
    $workspace = new Workspace();
    $workspace->name = 'Test Workspace Name';
    $workspace->owner_id = User::factory()->create()->id;
    $workspace->save();

    expect($workspace->slug)->toBe('test-workspace-name');
});

it('uses slug as route key', function (): void {
    $workspace = new Workspace();

    expect($workspace->getRouteKeyName())->toBe('slug');
});
