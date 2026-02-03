<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UploadInvoiceImportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class UploadInvoiceImportController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UploadInvoiceImportRequest $request): JsonResponse
    {
        $file = $request->validated()['file'];
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $filePath = $file->storeAs('imports/invoices', $filename);

        return response()->json([
            'file_path' => $filePath,
            'absolute_path' => storage_path('app/'.$filePath),
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
        ], 201);
    }
}
