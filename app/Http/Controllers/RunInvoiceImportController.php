<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RunInvoiceImportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Throwable;

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
        $absolutePath = storage_path('app/'.$filePath);

        try {
            $exitCode = Artisan::call('import:invoices', [
                'file' => $absolutePath,
                '--limit' => $limit,
                '--offset' => $offset,
            ]);

            return response()->json([
                'exit_code' => $exitCode,
                'output' => Artisan::output(),
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'No se pudo ejecutar la importación.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
