<?php

declare(strict_types=1);

use App\Models\PrescriptionItem;

test('to array', function (): void {
    $item = new PrescriptionItem([
        'id' => 1,
        'prescription_id' => 1,
        'mastertable_item_id' => 1,
        'mastertable_alias' => 'motivos_consulta',
    ]);

    expect(array_keys($item->toArray()))->toBe([
        'id',
        'prescription_id',
        'mastertable_item_id',
        'mastertable_alias',
    ]);
});
