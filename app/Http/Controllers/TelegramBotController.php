<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateTelegramBotAction;
use App\Actions\DeleteTelegramBotAction;
use App\Actions\UpdateTelegramBotAction;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\CreateTelegramBotRequest;
use App\Http\Requests\UpdateTelegramBotRequest;
use App\Models\TelegramBot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

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

    public function store(CreateTelegramBotRequest $request, CreateTelegramBotAction $action): RedirectResponse
    {
        try {
            $action->handle($request->user(), $request->validated());
        } catch (ActionValidationException $exception) {
            return redirect()->back()
                ->withInput()
                ->withErrors($exception->errors());
        }

        return redirect()->route('telegram-bots.index')
            ->with('success', 'Bot de Telegram agregado correctamente.');
    }

    public function edit(TelegramBot $telegramBot): Response
    {
        return Inertia::render('telegram-bots/edit', [
            'bot' => $telegramBot,
        ]);
    }

    public function update(UpdateTelegramBotRequest $request, TelegramBot $telegramBot, UpdateTelegramBotAction $action): RedirectResponse
    {
        try {
            $action->handle($telegramBot, $request->validated());
        } catch (ActionValidationException $exception) {
            return redirect()->back()
                ->withInput()
                ->withErrors($exception->errors());
        }

        return redirect()->route('telegram-bots.index')
            ->with('success', 'Bot de Telegram actualizado correctamente.');
    }

    public function destroy(TelegramBot $telegramBot, DeleteTelegramBotAction $action): RedirectResponse
    {
        $action->handle($telegramBot);

        return redirect()->route('telegram-bots.index')
            ->with('success', 'Bot de Telegram eliminado correctamente.');
    }
}
