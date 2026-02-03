<?php

declare(strict_types=1);

use App\Enums\ContactImportStatus;
use App\Models\ContactImport;

test('to array', function (): void {
    $contactImport = ContactImport::factory()->create()->refresh();

    expect(array_keys($contactImport->toArray()))->toBe([
        'id',
        'filename',
        'original_filename',
        'file_path',
        'source_files',
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
    $import = ContactImport::factory()->create([
        'status' => ContactImportStatus::Pending,
    ]);

    expect($import->isInProgress())->toBeTrue()
        ->and($import->isCompleted())->toBeFalse()
        ->and($import->hasFailed())->toBeFalse();

    $import->markAsMapping();
    $import->refresh();
    expect($import->status)->toBe(ContactImportStatus::Mapping);

    $import->markAsProcessing();
    $import->refresh();
    expect($import->status)->toBe(ContactImportStatus::Processing);

    $import->updateProgress(5, 4, 1);
    $import->refresh();
    expect($import->processed_rows)->toBe(5)
        ->and($import->successful_rows)->toBe(4)
        ->and($import->error_rows)->toBe(1);

    $import->markAsFailed([
        ['row' => 2, 'field' => 'name', 'message' => 'El nombre es obligatorio.'],
    ]);
    $import->refresh();
    expect($import->status)->toBe(ContactImportStatus::Failed);

    $import->markAsCompleted();
    $import->refresh();
    expect($import->status)->toBe(ContactImportStatus::Completed)
        ->and($import->imported_at)->not->toBeNull();
});
