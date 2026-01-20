<?php

declare(strict_types=1);

namespace App\Services\Automation\NodeRunners;

use App\Models\TelegramBot;
use App\Services\Automation\Support\AutomationContext;
use App\Services\Automation\Support\NodeResult;
use App\Services\Automation\Support\TemplateRenderer;
use InvalidArgumentException;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class TelegramMessageNodeRunner implements AutomationNodeRunner
{
    public function type(): string
    {
        return 'telegram.send_message';
    }

    public function run(AutomationContext $context, array $config, array $input): NodeResult
    {
        // Try to load from saved bot first
        $botToken = null;
        $chatId = null;

        if (! empty($config['telegram_bot_id'])) {
            $bot = TelegramBot::query()->find($config['telegram_bot_id']);

            if ($bot instanceof TelegramBot && $bot->is_active) {
                $botToken = $bot->bot_token;
                // Use saved default chat ID if no custom chat_id provided
                if (empty($config['chat_id']) && ! empty($bot->default_chat_id)) {
                    $chatId = $bot->default_chat_id;
                }
            }
        }

        // Fall back to manual config
        if ($botToken === null) {
            $botToken = $config['bot_token'] ?? null;
        }

        if (! is_string($botToken) || $botToken === '') {
            throw new InvalidArgumentException('Telegram node requires a bot_token or a saved bot.');
        }

        // Get chat_id from config if not already set from bot
        if ($chatId === null) {
            $chatId = $config['chat_id'] ?? null;
        }

        if ($chatId === null || $chatId === '') {
            throw new InvalidArgumentException('Telegram node requires a non-empty chat_id.');
        }

        $messageTemplate = $config['message'] ?? '';
        if (! is_string($messageTemplate) || $messageTemplate === '') {
            throw new InvalidArgumentException('Telegram node requires a non-empty message.');
        }

        $parseMode = $config['parse_mode'] ?? 'HTML';
        $disableNotification = (bool) ($config['disable_notification'] ?? false);

        // Render template variables
        $templateData = $context->toTemplateData($input);
        $renderedMessage = TemplateRenderer::renderString($messageTemplate, $templateData);

        // If chat_id contains template variables, render them too
        $renderedChatId = is_string($chatId)
            ? TemplateRenderer::renderString((string) $chatId, $templateData)
            : $chatId;

        try {
            $telegram = new Api($botToken);

            $response = $telegram->sendMessage([
                'chat_id' => $renderedChatId,
                'text' => $renderedMessage,
                'parse_mode' => $parseMode,
                'disable_notification' => $disableNotification,
            ]);

            return NodeResult::success([
                'message_id' => data_get($response, 'message_id'),
                'chat_id' => data_get($response, 'chat.id'),
                'date' => data_get($response, 'date'),
            ]);
        } catch (TelegramSDKException $e) {
            return NodeResult::failure([
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }
    }
}
