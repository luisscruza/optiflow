<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RNCRetrievalRequest;
use App\Models\RNC;
use Illuminate\Http\JsonResponse;

final class RNCRetrieveController extends Controller
{
    public function __invoke(RNCRetrievalRequest $request): JsonResponse
    {
        $rnc = RNC::query()->findOrFail($request->string('rnc')->value())->except('created_at', 'updated_at');

        return response()->json($rnc);
    }
}
