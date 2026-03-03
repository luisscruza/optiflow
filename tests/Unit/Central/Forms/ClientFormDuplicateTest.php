<?php

declare(strict_types=1);

use App\Models\Central\Client;

test('detects duplicate client by email', function (): void {
    $existing = Client::factory()->create(['email' => 'duplicate@example.com']);

    $found = Client::query()->where('email', 'duplicate@example.com')->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($existing->id);
});

test('detects duplicate client by phone', function (): void {
    $existing = Client::factory()->create(['phone_primary' => '+1-555-0001']);

    $found = Client::query()->where('phone_primary', '+1-555-0001')->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($existing->id);
});

test('does not detect self as duplicate when editing by email', function (): void {
    $record = Client::factory()->create(['email' => 'self@example.com']);

    $found = Client::query()
        ->where('email', 'self@example.com')
        ->where('id', '!=', $record->id)
        ->first();

    expect($found)->toBeNull();
});

test('does not detect self as duplicate when editing by phone', function (): void {
    $record = Client::factory()->create(['phone_primary' => '+1-555-0002']);

    $found = Client::query()
        ->where('phone_primary', '+1-555-0002')
        ->where('id', '!=', $record->id)
        ->first();

    expect($found)->toBeNull();
});

test('returns null when no duplicate exists for email', function (): void {
    $found = Client::query()->where('email', 'nonexistent@example.com')->first();

    expect($found)->toBeNull();
});

test('returns null when no duplicate exists for phone', function (): void {
    $found = Client::query()->where('phone_primary', '+1-000-0000')->first();

    expect($found)->toBeNull();
});
