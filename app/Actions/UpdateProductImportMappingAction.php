<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\ProductImport;
use Illuminate\Support\Facades\DB;

final readonly class UpdateProductImportMappingAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ProductImport $productImport, array $data): ProductImport
    {
        return DB::transaction(function () use ($productImport, $data): ProductImport {
            if ($productImport->status !== ProductImport::STATUS_MAPPING) {
                throw new ActionValidationException([
                    'general' => 'Import is not in mapping stage',
                ]);
            }

            $productImport->update([
                'column_mapping' => $data['column_mapping'],
            ]);

            return $productImport;
        });
    }
}
