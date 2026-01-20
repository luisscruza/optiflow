<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateBulkQuotationPdfArchiveAction;
use App\Exceptions\ActionNotFoundException;
use Illuminate\Http\Request;

final class BulkDownloadQuotationPdfController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, GenerateBulkQuotationPdfArchiveAction $action)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'max:10'],
            'ids.*' => ['required', 'integer', 'exists:quotations,id'],
        ]);

        try {
            $result = $action->handle($validated['ids']);
        } catch (ActionNotFoundException $exception) {
            abort(404, $exception->getMessage());
        }

        return response()->download($result['path'], $result['filename'])->deleteFileAfterSend(true);
    }
}
