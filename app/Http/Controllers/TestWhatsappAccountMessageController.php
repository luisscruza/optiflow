<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SendTestWhatsappAccountMessageAction;
use App\Exceptions\ActionValidationException;
use App\Models\WhatsappAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TestWhatsappAccountMessageController extends Controller
{
    /**
     * Send a test message.
     */
    public function __invoke(Request $request, WhatsappAccount $whatsappAccount, SendTestWhatsappAccountMessageAction $action): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        try {
            $result = $action->handle($whatsappAccount, $validated['to'], $validated['message']);
        } catch (ActionValidationException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->errors()['error'] ?? $exception->getMessage(),
            ], 422);
        }

        return response()->json($result);
    }
}
