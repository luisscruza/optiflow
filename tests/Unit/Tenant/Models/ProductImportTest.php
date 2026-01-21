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

test('updates import status and progress', function (): void {
    $import = ProductImport::factory()->create([
        'status' => ProductImport::STATUS_PENDING,
    ]);

    expect($import->isInProgress())->toBeTrue()
        ->and($import->isCompleted())->toBeFalse()
        ->and($import->hasFailed())->toBeFalse();

    $import->markAsMapping();
    $import->refresh();
    expect($import->status)->toBe(ProductImport::STATUS_MAPPING);

    $import->markAsProcessing();
    $import->refresh();
    expect($import->status)->toBe(ProductImport::STATUS_PROCESSING);

    $import->updateProgress(5, 4, 1);
    $import->refresh();
    expect($import->processed_rows)->toBe(5)
        ->and($import->successful_rows)->toBe(4)
        ->and($import->error_rows)->toBe(1);

    $import->markAsFailed(['row' => 'invalid']);
    $import->refresh();
    expect($import->status)->toBe(ProductImport::STATUS_FAILED);

    $import->markAsCompleted();
    $import->refresh();
    expect($import->status)->toBe(ProductImport::STATUS_COMPLETED)
        ->and($import->imported_at)->not->toBeNull();
});
