<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateInvoicePdfAction;
use App\Models\Invoice;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DownloadInvoicePdfController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Invoice $invoice, GenerateInvoicePdfAction $action): BinaryFileResponse
    {
        $result = $action->handle($invoice);

        return response()->download($result['path'], $result['filename'])->deleteFileAfterSend(true);
    }
}
