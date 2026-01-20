<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessProductImportAction;
use App\Http\Requests\ProcessImportRequest;
use App\Models\ProductImport;
use App\Models\Workspace;
use Exception;
use Illuminate\Http\RedirectResponse;

final class ProcessProductImportController
{
    public function __construct(private readonly ProcessProductImportAction $processProductImportAction) {}

    /**
     * Process the import.
     */
    public function __invoke(ProcessImportRequest $request, ProductImport $productImport): RedirectResponse
    {
        if ($productImport->status !== ProductImport::STATUS_MAPPING) {
            return redirect()->route('product-imports.show', $productImport->id)
                ->withErrors(['general' => 'Import is not ready for processing']);
        }

        if (! $productImport->column_mapping) {
            return redirect()->route('product-imports.show', $productImport->id)
                ->withErrors(['general' => 'Column mapping is required before processing']);
        }

        try {
            $validatedData = $request->validated();
            $workspaceIds = $validatedData['workspaces'];
            $stockMapping = $validatedData['stock_mapping'] ?? [];

            $workspaces = Workspace::query()->whereIn('id', $workspaceIds)->get();

            // Process the import in the background (or synchronously for now)
            $result = $this->processProductImportAction->handle($productImport, $workspaces, $stockMapping);

            if ($result['errors'] === 0) {
                return redirect()->route('product-imports.show', $productImport->id)
                    ->with('success', "Import completed successfully! {$result['imported']} products imported.");
            }

            return redirect()->route('product-imports.show', $productImport->id)
                ->with('warning', "Import completed with {$result['errors']} errors. {$result['imported']} products imported successfully.");
        } catch (Exception $e) {
            $productImport->markAsFailed(['general' => $e->getMessage()]);

            return redirect()->route('product-imports.show', $productImport->id)
                ->withErrors(['general' => 'Import failed: '.$e->getMessage()]);
        }
    }
}
