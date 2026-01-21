<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;

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

it('has an owner', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);

    expect($workspace->owner)->toBeInstanceOf(User::class);
    expect($workspace->owner->id)->toBe($user->id);
});

it('has many invoices', function (): void {
    $workspace = Workspace::factory()->create();
    Workspace::factory()->create();
    $workspace->invoices()->saveMany(Invoice::factory()->count(3)->make());

    expect($workspace->invoices()->count())->toBe(3);
});

it('has many invitations', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create();
    Workspace::factory()->create();
    $workspace->invitations()->createMany([
        ['email' => 'test@example.com', 'token' => Str::random(32), 'invited_by' => $user->id, 'role' => UserRole::User, 'expires_at' => now()->addDays(7)],
        ['email' => 'test2@example.com', 'token' => Str::random(32), 'invited_by' => $user->id, 'role' => UserRole::Admin, 'expires_at' => now()->addDays(7)],
    ]);
    expect($workspace->invitations()->count())->toBe(2);
});

it('to array', function (): void {
    $workspace = Workspace::factory()->create()->refresh();

    expect(array_keys($workspace->toArray()))->toBe([
        'id',
        'name',
        'code',
        'slug',
        'description',
        'owner_id',
        'settings',
        'is_active',
        'is_default',
        'created_at',
        'updated_at',
        'address',
        'phone',
        'members_count',
    ]);
});
