<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\TelegramBot;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class UpdateTelegramBotAction
{
    public function handle(TelegramBot $telegramBot, array $data): TelegramBot
    {
        $updateData = [
            'name' => $data['name'],
            'default_chat_id' => $data['default_chat_id'] ?? null,
            'is_active' => $data['is_active'],
        ];

        if (! empty($data['bot_token'])) {
            $botUsername = $this->resolveBotUsername($data['bot_token']);
            $updateData['bot_username'] = $botUsername;
            $updateData['bot_token'] = $data['bot_token'];
        }

        return DB::transaction(function () use ($telegramBot, $updateData): TelegramBot {
            $telegramBot->update($updateData);

            return $telegramBot;
        });
    }

    private function resolveBotUsername(string $token): string
    {
        try {
            $telegram = new Api($token);
            /** @var \Telegram\Bot\Objects\User $botInfo */
            $botInfo = $telegram->getMe();
            $botUsername = $botInfo->username;

            if (! is_string($botUsername) || $botUsername === '') {
                throw new ActionValidationException([
                    'bot_token' => 'Token inválido: no se pudo obtener el usuario.',
                ]);
            }

            return $botUsername;
        } catch (TelegramSDKException $e) {
            throw new ActionValidationException([
                'bot_token' => 'Token inválido: '.$e->getMessage(),
            ]);
        }
    }
}
