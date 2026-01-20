<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\TelegramBot;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class SendTestTelegramBotMessageAction
{
    /**
     * @return array{success: bool, message_id: int|null}
     */
    public function handle(TelegramBot $telegramBot, string $chatId, string $message): array
    {
        try {
            $telegram = new Api($telegramBot->bot_token);

            /** @var \Telegram\Bot\Objects\Message $response */
            $response = $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            return [
                'success' => true,
                'message_id' => $response->messageId,
            ];
        } catch (TelegramSDKException $exception) {
            throw new ActionValidationException([
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
