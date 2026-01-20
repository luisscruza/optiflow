<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateTelegramBotRequest;
use App\Http\Requests\UpdateTelegramBotRequest;
use App\Models\TelegramBot;
use Illuminate\Http\RedirectResponse;
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

    public function store(CreateTelegramBotRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Verify the token by getting bot info
        try {
            $telegram = new Api($validated['bot_token']);
            /** @var \Telegram\Bot\Objects\User $botInfo */
            $botInfo = $telegram->getMe();
            $botUsername = $botInfo->username;
            if (! is_string($botUsername) || $botUsername === '') {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['bot_token' => 'Token inválido: no se pudo obtener el usuario.']);
            }
        } catch (TelegramSDKException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['bot_token' => 'Token inválido: '.$e->getMessage()]);
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

    public function update(UpdateTelegramBotRequest $request, TelegramBot $telegramBot): RedirectResponse
    {
        $validated = $request->validated();

        $updateData = [
            'name' => $validated['name'],
            'default_chat_id' => $validated['default_chat_id'] ?? null,
            'is_active' => $validated['is_active'],
        ];

        // Only update token if provided
        if (! empty($validated['bot_token'])) {
            try {
                $telegram = new Api($validated['bot_token']);
                /** @var \Telegram\Bot\Objects\User $botInfo */
                $botInfo = $telegram->getMe();
                $botUsername = $botInfo->username;
                if (! is_string($botUsername) || $botUsername === '') {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['bot_token' => 'Token inválido: no se pudo obtener el usuario.']);
                }

                $updateData['bot_username'] = $botUsername;
                $updateData['bot_token'] = $validated['bot_token'];
            } catch (TelegramSDKException $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['bot_token' => 'Token inválido: '.$e->getMessage()]);
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
}
