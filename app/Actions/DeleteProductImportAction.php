<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\ProductImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final readonly class DeleteProductImportAction
{
    public function handle(ProductImport $productImport): void
    {
        DB::transaction(function () use ($productImport): void {
            try {
                if (Storage::exists($productImport->file_path)) {
                    Storage::delete($productImport->file_path);
                }

                $productImport->delete();
            } catch (Throwable $exception) {
                throw new ActionValidationException([
                    'general' => 'Failed to delete import: '.$exception->getMessage(),
                ]);
            }
        });
    }
}
