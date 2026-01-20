<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TelegramBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class ListTelegramBotsController
{
    /**
     * API endpoint to list bots for automation builder.
     */
    public function __invoke(): JsonResponse
    {
        $bots = TelegramBot::query()
            ->where('workspace_id', Auth::user()->current_workspace_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'bot_username', 'default_chat_id']);

        return response()->json($bots);
    }
}
