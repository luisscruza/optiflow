<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateWhatsappAccountAction;
use App\Actions\DeleteWhatsappAccountAction;
use App\Actions\UpdateWhatsappAccountAction;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\CreateWhatsappAccountRequest;
use App\Http\Requests\UpdateWhatsappAccountRequest;
use App\Models\WhatsappAccount;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class WhatsappAccountController
{
    public function index(): Response
    {
        $accounts = WhatsappAccount::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('whatsapp-accounts/index', [
            'accounts' => $accounts,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('whatsapp-accounts/create');
    }

    public function store(CreateWhatsappAccountRequest $request, CreateWhatsappAccountAction $action): RedirectResponse
    {
        try {
            $action->handle($request->validated());
        } catch (ActionValidationException $exception) {
            return redirect()->back()
                ->withInput()
                ->withErrors($exception->errors());
        }

        return redirect()->route('whatsapp-accounts.index')
            ->with('success', 'Cuenta de WhatsApp agregada correctamente.');
    }

    public function edit(WhatsappAccount $whatsappAccount): Response
    {
        return Inertia::render('whatsapp-accounts/edit', [
            'account' => $whatsappAccount,
        ]);
    }

    public function update(UpdateWhatsappAccountRequest $request, WhatsappAccount $whatsappAccount, UpdateWhatsappAccountAction $action): RedirectResponse
    {
        try {
            $action->handle($whatsappAccount, $request->validated());
        } catch (ActionValidationException $exception) {
            return redirect()->back()
                ->withInput()
                ->withErrors($exception->errors());
        }

        return redirect()->route('whatsapp-accounts.index')
            ->with('success', 'Cuenta de WhatsApp actualizada correctamente.');
    }

    public function destroy(WhatsappAccount $whatsappAccount, DeleteWhatsappAccountAction $action): RedirectResponse
    {
        $action->handle($whatsappAccount);

        return redirect()->route('whatsapp-accounts.index')
            ->with('success', 'Cuenta de WhatsApp eliminada correctamente.');
    }
}
