<?php

declare(strict_types=1);

use App\Models\ProductImport;

test('to array', function (): void {
    $productImport = ProductImport::factory()->create()->refresh();

    expect(array_keys($productImport->toArray()))->toBe([
        'id',
        'filename',
        'original_filename',
        'file_path',
        'status',
        'headers',
        'column_mapping',
        'import_data',
        'validation_errors',
        'import_summary',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'error_rows',
        'imported_at',
        'created_at',
        'updated_at',
    ]);
});
