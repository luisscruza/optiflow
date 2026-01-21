<?php

declare(strict_types=1);

use Database\Factories\TelegramBotFactory;

test('to array', function (): void {
    $bot = TelegramBotFactory::new()->create()->refresh();

    expect(array_keys($bot->toArray()))->toBe([
        'id',
        'workspace_id',
        'name',
        'bot_username',
        'default_chat_id',
        'is_active',
        'created_at',
        'updated_at',
    ]);
});
