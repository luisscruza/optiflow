<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactImportStatus;
use App\Exceptions\ActionValidationException;
use App\Models\ContactImport;
use Illuminate\Support\Facades\DB;

final readonly class UpdateContactImportMappingAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ContactImport $contactImport, array $data): ContactImport
    {
        return DB::transaction(function () use ($contactImport, $data): ContactImport {
            if ($contactImport->status !== ContactImportStatus::Mapping) {
                throw new ActionValidationException([
                    'general' => 'Import is not in mapping stage',
                ]);
            }

            $contactImport->update([
                'column_mapping' => $data['column_mapping'],
            ]);

            return $contactImport;
        });
    }
}
