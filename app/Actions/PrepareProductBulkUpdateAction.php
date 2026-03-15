<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ProductBulkUpdate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class PrepareProductBulkUpdateAction
{
    public function __construct(
        private ParseProductBulkUpdateCsvAction $parseProductBulkUpdateCsvAction,
        private AnalyzeProductBulkUpdateAction $analyzeProductBulkUpdateAction,
    ) {}

    public function handle(UploadedFile $file, User $user): ProductBulkUpdate
    {
        $path = $file->storeAs('product-bulk-updates', Str::uuid()->toString().'.'.$file->getClientOriginalExtension());

        if ($path === false) {
            throw new RuntimeException('Failed to store the uploaded CSV file.');
        }

        $bulkUpdate = ProductBulkUpdate::query()->create([
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => ProductBulkUpdate::STATUS_PENDING,
        ]);

        $parsedFile = $this->parseProductBulkUpdateCsvAction->handle($bulkUpdate);

        if (! in_array('SKU', $parsedFile['headers'], true)) {
            $bulkUpdate->update([
                'status' => ProductBulkUpdate::STATUS_FAILED,
                'headers' => $parsedFile['headers'],
                'total_rows' => $parsedFile['total_rows'],
                'error_rows' => $parsedFile['total_rows'],
                'validation_errors' => [[
                    'row' => 1,
                    'field' => 'SKU',
                    'message' => 'The CSV must include the SKU column.',
                ]],
            ]);

            return $bulkUpdate->fresh();
        }

        $analysis = $this->analyzeProductBulkUpdateAction->handle($parsedFile['data'], $user);

        $bulkUpdate->update([
            'status' => $analysis['validation_errors'] === [] ? ProductBulkUpdate::STATUS_READY : ProductBulkUpdate::STATUS_FAILED,
            'headers' => $parsedFile['headers'],
            'preview_rows' => $analysis['preview_rows'],
            'validation_errors' => $analysis['validation_errors'],
            'summary' => $analysis['summary'],
            'total_rows' => $parsedFile['total_rows'],
            'error_rows' => $analysis['summary']['rows_failed'],
        ]);

        return $bulkUpdate->fresh();
    }
}
