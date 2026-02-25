<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RunInvoiceImportRequest;
use App\Jobs\ProcessInvoiceImportJob;
use App\Models\InvoiceImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

final class RunInvoiceImportController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RunInvoiceImportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $filePath = $validated['file_path'];

        if (! Storage::exists($filePath)) {
            return response()->json([
                'message' => 'El archivo seleccionado no existe.',
            ], 422);
        }

        $limit = (int) ($validated['limit'] ?? 50);
        $offset = (int) ($validated['offset'] ?? 0);

        $filename = (string) ($validated['filename'] ?? basename($filePath));
        $originalFilename = (string) ($validated['original_filename'] ?? $filename);

        $import = InvoiceImport::query()->create([
            'user_id' => Auth::id(),
            'filename' => $filename,
            'original_filename' => $originalFilename,
            'file_path' => $filePath,
            'limit' => $limit,
            'offset' => $offset,
            'status' => \App\Enums\InvoiceImportStatus::Pending,
        ]);

        dispatch(new ProcessInvoiceImportJob(invoiceImportId: $import->id));

        return response()->json([
            'import' => $import->fresh(),
        ], 202);
    }
}
