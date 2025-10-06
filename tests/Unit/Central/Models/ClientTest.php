<?php

declare(strict_types=1);

use App\Models\Central\Client;

beforeEach(function (): void {
    Event::fake();
});

test('to array', function (): void {
    $client = Client::factory()->create()->refresh();

    expect(array_keys($client->toArray()))
        ->toBe([
            'id',
            'name',
            'email',
            'phone_primary',
            'created_at',
            'updated_at',
        ]);
});

test('has many tenants', function (): void {
    $client = Client::factory()->hasTenants(3)
        ->create()
        ->refresh();

    expect($client->tenants()->count())->toBe(3);
});
