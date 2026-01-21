<?php

declare(strict_types=1);

use App\Models\DocumentSubtype;
use Carbon\Carbon;

test('to array', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create()->refresh();

    expect(array_keys($documentSubtype->toArray()))->toBe([
        'id',
        'name',
        'type',
        'is_default',
        'valid_until_date',
        'prefix',
        'start_number',
        'end_number',
        'next_number',
        'created_at',
        'updated_at',
    ]);
});

test('handles ncf sequence checks', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'next_number' => 1,
        'end_number' => 200,
        'valid_until_date' => Carbon::now()->addDays(10),
    ]);

    expect($documentSubtype->isValid())->toBeTrue()
        ->and($documentSubtype->generateNCF())->toBe('B0100000001');

    $next = $documentSubtype->getNextNcfNumber();
    $documentSubtype->refresh();

    expect($next)->toBe('B0100000001')
        ->and($documentSubtype->next_number)->toBe(2)
        ->and($documentSubtype->isRunningLow())->toBeFalse();

    $documentSubtype->update([
        'next_number' => 150,
        'end_number' => 200,
    ]);

    expect($documentSubtype->isRunningLow())->toBeTrue();

    $documentSubtype->update([
        'valid_until_date' => Carbon::now()->addDays(5),
    ]);

    expect($documentSubtype->isNearExpiration())->toBeTrue();
});
