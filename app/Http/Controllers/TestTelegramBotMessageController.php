<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TelegramBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class TestTelegramBotMessageController extends Controller
{
    /**
     * Test sending a message with a bot.
     */
    public function __invoke(Request $request, TelegramBot $telegramBot): JsonResponse
    {
        $validated = $request->validate([
            'chat_id' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        try {
            $telegram = new Api($telegramBot->bot_token);

            /** @var \Telegram\Bot\Objects\Message $response */
            $response = $telegram->sendMessage([
                'chat_id' => $validated['chat_id'],
                'text' => $validated['message'],
                'parse_mode' => 'HTML',
            ]);

            return response()->json([
                'success' => true,
                'message_id' => $response->messageId,
            ]);
        } catch (TelegramSDKException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
