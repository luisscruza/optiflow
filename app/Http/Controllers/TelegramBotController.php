<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TelegramBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class TelegramBotController extends Controller
{
    public function index(): Response
    {
        $bots = TelegramBot::query()
            ->where('workspace_id', Auth::user()->current_workspace_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('telegram-bots/index', [
            'bots' => $bots,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('telegram-bots/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bot_token' => ['required', 'string'],
            'default_chat_id' => ['nullable', 'string', 'max:255'],
        ]);

        // Verify the token by getting bot info
        try {
            $telegram = new Api($validated['bot_token']);
            $botInfo = $telegram->getMe();
            $botUsername = $botInfo->getUsername();
        } catch (TelegramSDKException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['bot_token' => 'Token inválido: ' . $e->getMessage()]);
        }

        TelegramBot::query()->create([
            'workspace_id' => Auth::user()->current_workspace_id,
            'name' => $validated['name'],
            'bot_username' => $botUsername,
            'bot_token' => $validated['bot_token'],
            'default_chat_id' => $validated['default_chat_id'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('telegram-bots.index')
            ->with('success', 'Bot de Telegram agregado correctamente.');
    }

    public function edit(TelegramBot $telegramBot): Response
    {
        return Inertia::render('telegram-bots/edit', [
            'bot' => $telegramBot,
        ]);
    }

    public function update(Request $request, TelegramBot $telegramBot): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bot_token' => ['nullable', 'string'],
            'default_chat_id' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'default_chat_id' => $validated['default_chat_id'] ?? null,
            'is_active' => $validated['is_active'],
        ];

        // Only update token if provided
        if (! empty($validated['bot_token'])) {
            try {
                $telegram = new Api($validated['bot_token']);
                $botInfo = $telegram->getMe();
                $updateData['bot_username'] = $botInfo->getUsername();
                $updateData['bot_token'] = $validated['bot_token'];
            } catch (TelegramSDKException $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['bot_token' => 'Token inválido: ' . $e->getMessage()]);
            }
        }

        $telegramBot->update($updateData);

        return redirect()->route('telegram-bots.index')
            ->with('success', 'Bot de Telegram actualizado correctamente.');
    }

    public function destroy(TelegramBot $telegramBot): RedirectResponse
    {
        $telegramBot->delete();

        return redirect()->route('telegram-bots.index')
            ->with('success', 'Bot de Telegram eliminado correctamente.');
    }

    /**
     * API endpoint to list bots for automation builder.
     */
    public function list(): JsonResponse
    {
        $bots = TelegramBot::query()
            ->where('workspace_id', Auth::user()->current_workspace_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'bot_username', 'default_chat_id']);

        return response()->json($bots);
    }

    /**
     * Test sending a message with a bot.
     */
    public function testMessage(Request $request, TelegramBot $telegramBot): JsonResponse
    {
        $validated = $request->validate([
            'chat_id' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        try {
            $telegram = new Api($telegramBot->bot_token);

            $response = $telegram->sendMessage([
                'chat_id' => $validated['chat_id'],
                'text' => $validated['message'],
                'parse_mode' => 'HTML',
            ]);

            return response()->json([
                'success' => true,
                'message_id' => $response->getMessageId(),
            ]);
        } catch (TelegramSDKException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
