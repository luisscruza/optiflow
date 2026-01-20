<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateQuotationPdfAction;
use App\Models\Quotation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DownloadQuotationPdfController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Quotation $quotation, GenerateQuotationPdfAction $action): BinaryFileResponse
    {
        $result = $action->handle($quotation);

        return response()->download($result['path'], $result['filename'])->deleteFileAfterSend(true);
    }
}
