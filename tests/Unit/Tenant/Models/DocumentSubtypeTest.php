<?php

declare(strict_types=1);

use App\Models\DocumentSubtype;

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
