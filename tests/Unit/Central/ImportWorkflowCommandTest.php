<?php

declare(strict_types=1);

use App\Console\Commands\ImportWorkflow;
use Illuminate\Support\Facades\File;

test('workflow import command accepts an optional json file argument', function (): void {
    $command = app(ImportWorkflow::class);
    $fileArgument = $command->getDefinition()->getArgument('file');

    expect($command->getDescription())->toBe('Import workflows from JSON file');
    expect($fileArgument->isRequired())->toBeFalse();
    expect($fileArgument->getDescription())->toContain('JSON file path');
});

test('workflow import command reads json records and cleans scalar values', function (): void {
    $path = storage_path('app/import-workflow-command-test.json');

    File::put($path, json_encode([
        [
            'TYPE' => 'Elaboracion de lentes',
            'CLIENT' => 'Cliente Dos',
            'prescription_id' => 15,
            'delivered' => true,
            'notes' => null,
        ],
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

    $command = app(ImportWorkflow::class);

    $readJsonRecords = Closure::bind(function (string $filePath): array {
        return $this->readJsonRecords($filePath);
    }, $command, ImportWorkflow::class);

    $cleanUtf8Record = Closure::bind(function (array $record): array {
        return $this->cleanUtf8Record($record);
    }, $command, ImportWorkflow::class);

    $records = $readJsonRecords($path);
    $cleanedRecord = $cleanUtf8Record($records[0]);

    expect($records)->toHaveCount(1);
    expect($cleanedRecord['TYPE'])->toBe('Elaboracion de lentes');
    expect($cleanedRecord['CLIENT'])->toBe('Cliente Dos');
    expect($cleanedRecord['prescription_id'])->toBe('15');
    expect($cleanedRecord['delivered'])->toBe('1');
    expect($cleanedRecord['notes'])->toBe('');

    File::delete($path);
});
