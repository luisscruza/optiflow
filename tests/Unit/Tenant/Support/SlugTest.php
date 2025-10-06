<?php

declare(strict_types=1);

use App\Models\Workspace;
use App\Support\Slug;

it('generates an slug from a string', function (): void {
    $name = 'Test Workspace Name';
    $slug = Slug::generateUniqueSlug($name, Workspace::class);

    expect($slug)->toBe('test-workspace-name');
});

it('appends a counter to the slug if it already exists', function (): void {
    $name = 'Test Workspace Name';
    Workspace::factory()->create(['name' => $name, 'slug' => 'test-workspace-name']);
    $slug = Slug::generateUniqueSlug($name, Workspace::class);

    expect($slug)->toBe('test-workspace-name-1');
});
