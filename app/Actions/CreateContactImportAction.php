<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactImportStatus;
use App\Exceptions\ActionValidationException;
use App\Models\ContactImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class CreateContactImportAction
{
    public function __construct(private ParseCsvFileAction $parseCsvFileAction) {}

    public function handle(UploadedFile $file): ContactImport
    {
        return $this->handleMany([$file]);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function handleMany(array $files): ContactImport
    {
        if ($files === []) {
            throw new ActionValidationException([
                'files' => 'Debe seleccionar al menos un archivo CSV.',
            ]);
        }

        $storedFiles = [];
        foreach ($files as $index => $file) {
            $storedFiles[] = $this->storeFile($file, $index === 0);
        }

        return DB::transaction(function () use ($storedFiles): ContactImport {
            $primaryFile = $storedFiles[0];

            $import = ContactImport::query()->create([
                'filename' => $primaryFile['filename'],
                'original_filename' => $primaryFile['original_filename'],
                'file_path' => $primaryFile['file_path'],
                'source_files' => $storedFiles,
                'status' => ContactImportStatus::Pending,
            ]);

            $headers = null;
            $preview = [];
            $totalRows = 0;
            $previewLimit = 50;

            foreach ($storedFiles as $storedFile) {
                $remaining = max(0, $previewLimit - count($preview));

                try {
                    $parseResult = $this->parseCsvFileAction->handle(
                        $import,
                        $remaining,
                        $headers,
                        (bool) ($storedFile['has_header'] ?? false),
                        $storedFile['file_path']
                    );
                } catch (RuntimeException $exception) {
                    throw new ActionValidationException([
                        'file' => 'Failed to process file: '.$exception->getMessage(),
                    ]);
                }

                if ($headers === null) {
                    $headers = $parseResult['headers'];
                }

                $preview = array_merge($preview, $parseResult['data']);
                $totalRows += $parseResult['total_rows'];
            }

            $import->update([
                'headers' => $headers ?? [],
                'import_data' => $preview,
                'total_rows' => $totalRows,
                'status' => ContactImportStatus::Mapping,
            ]);

            return $import;
        });
    }

    /**
     * @return array{filename: string, original_filename: string, file_path: string, has_header: bool}
     */
    private function storeFile(UploadedFile $file, bool $hasHeader): array
    {
        try {
            $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
            $filePath = $file->storeAs('imports', $filename);
        } catch (RuntimeException $exception) {
            throw new ActionValidationException([
                'file' => 'Failed to process file: '.$exception->getMessage(),
            ]);
        }

        return [
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'has_header' => $hasHeader,
        ];
    }
}
