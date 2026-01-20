<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\TelegramBot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class CreateTelegramBotAction
{
    public function handle(User $user, array $data): TelegramBot
    {
        $botUsername = $this->resolveBotUsername($data['bot_token']);

        return DB::transaction(function () use ($user, $data, $botUsername): TelegramBot {
            return TelegramBot::query()->create([
                'workspace_id' => $user->current_workspace_id,
                'name' => $data['name'],
                'bot_username' => $botUsername,
                'bot_token' => $data['bot_token'],
                'default_chat_id' => $data['default_chat_id'] ?? null,
                'is_active' => true,
            ]);
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
