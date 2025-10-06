<?php

declare(strict_types=1);

use App\Models\Workspace;
use App\Support\Slug;

it('generates an slug from a string', function (): void {
    $name = 'Test Workspace Name';
    $slug = Slug::generateUniqueSlug($name, Workspace::class);

    expect($slug)->toBe('test-workspace-name');
});