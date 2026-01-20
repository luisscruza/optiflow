<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SendTestTelegramBotMessageAction;
use App\Exceptions\ActionValidationException;
use App\Models\TelegramBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TestTelegramBotMessageController extends Controller
{
    /**
     * Test sending a message with a bot.
     */
    public function __invoke(Request $request, TelegramBot $telegramBot, SendTestTelegramBotMessageAction $action): JsonResponse
    {
        $validated = $request->validate([
            'chat_id' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        try {
            $result = $action->handle($telegramBot, $validated['chat_id'], $validated['message']);
        } catch (ActionValidationException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->errors()['error'] ?? $exception->getMessage(),
            ], 422);
        }

        return response()->json($result);
    }
}
