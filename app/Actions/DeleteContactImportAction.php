<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\ContactImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final readonly class DeleteContactImportAction
{
    public function handle(ContactImport $contactImport): void
    {
        DB::transaction(function () use ($contactImport): void {
            try {
                if (Storage::exists($contactImport->file_path)) {
                    Storage::delete($contactImport->file_path);
                }

                $contactImport->delete();
            } catch (Throwable $exception) {
                throw new ActionValidationException([
                    'general' => 'Failed to delete import: '.$exception->getMessage(),
                ]);
            }
        });
    }
}
