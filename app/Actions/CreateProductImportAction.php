<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\ProductImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class CreateProductImportAction
{
    public function __construct(private ParseExcelFileAction $parseExcelFileAction) {}

    public function handle(UploadedFile $file): ProductImport
    {
        try {
            $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
            $filePath = $file->storeAs('imports', $filename);
        } catch (RuntimeException $exception) {
            throw new ActionValidationException([
                'file' => 'Failed to process file: '.$exception->getMessage(),
            ]);
        }

        return DB::transaction(function () use ($file, $filename, $filePath): ProductImport {
            $import = ProductImport::query()->create([
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'status' => ProductImport::STATUS_PENDING,
            ]);

            try {
                $parseResult = $this->parseExcelFileAction->handle($import, 50);
            } catch (RuntimeException $exception) {
                throw new ActionValidationException([
                    'file' => 'Failed to process file: '.$exception->getMessage(),
                ]);
            }

            $import->update([
                'headers' => $parseResult['headers'],
                'import_data' => $parseResult['data'],
                'total_rows' => $parseResult['total_rows'],
                'status' => ProductImport::STATUS_MAPPING,
            ]);

            return $import;
        });
    }
}
