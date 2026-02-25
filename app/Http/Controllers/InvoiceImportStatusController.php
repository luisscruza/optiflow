<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InvoiceImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class InvoiceImportStatusController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(InvoiceImport $invoiceImport): JsonResponse
    {
        abort_unless(Auth::check() && $invoiceImport->user_id === Auth::id(), 403);

        return response()->json([
            'import' => $invoiceImport,
        ]);
    }
}
