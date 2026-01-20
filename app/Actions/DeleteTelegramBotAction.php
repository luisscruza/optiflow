<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TelegramBot;
use Illuminate\Support\Facades\DB;

final class DeleteTelegramBotAction
{
    public function handle(TelegramBot $telegramBot): void
    {
        DB::transaction(function () use ($telegramBot): void {
            $telegramBot->delete();
        });
    }
}
