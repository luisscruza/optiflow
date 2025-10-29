<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ValidateNFCAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\NfcValidationRequest;
use Illuminate\Http\JsonResponse;

final class NcfValidationController extends Controller
{
    /**
     * Validate a manual NCF entry.
     */
    public function __invoke(NfcValidationRequest $request, ValidateNFCAction $action): JsonResponse
    {

        $ncf = $request->string('ncf')->value();
        $invoiceId = $request->integer('invoice_id');

        $result = $action->handle($ncf, $invoiceId);

        return response()->json($result);
    }
}
