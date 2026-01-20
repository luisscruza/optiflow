<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

final class WhatsappAccountController extends Controller
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number_id' => ['required', 'string', 'max:255'],
            'business_account_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['required', 'string'],
        ]);

        // Verify the token by calling WhatsApp API
        $verification = $this->verifyCredentials($validated['phone_number_id'], $validated['access_token']);

        if (! $verification['valid']) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['access_token' => 'Credenciales inválidas: '.$verification['error']]);
        }

        WhatsappAccount::query()->create([
            'name' => $validated['name'],
            'phone_number_id' => $validated['phone_number_id'],
            'business_account_id' => $validated['business_account_id'] ?? null,
            'access_token' => $validated['access_token'],
            'display_phone_number' => $verification['display_phone_number'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('whatsapp-accounts.index')
            ->with('success', 'Cuenta de WhatsApp agregada correctamente.');
    }

    public function edit(WhatsappAccount $whatsappAccount): Response
    {
        return Inertia::render('whatsapp-accounts/edit', [
            'account' => $whatsappAccount,
        ]);
    }

    public function update(Request $request, WhatsappAccount $whatsappAccount): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number_id' => ['required', 'string', 'max:255'],
            'business_account_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'phone_number_id' => $validated['phone_number_id'],
            'business_account_id' => $validated['business_account_id'] ?? null,
            'is_active' => $validated['is_active'],
        ];

        // Only update token if provided
        if (! empty($validated['access_token'])) {
            $verification = $this->verifyCredentials($validated['phone_number_id'], $validated['access_token']);

            if (! $verification['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['access_token' => 'Credenciales inválidas: '.$verification['error']]);
            }

            $updateData['access_token'] = $validated['access_token'];
            $updateData['display_phone_number'] = $verification['display_phone_number'] ?? null;
        }

        $whatsappAccount->update($updateData);

        return redirect()->route('whatsapp-accounts.index')
            ->with('success', 'Cuenta de WhatsApp actualizada correctamente.');
    }

    public function destroy(WhatsappAccount $whatsappAccount): RedirectResponse
    {
        $whatsappAccount->delete();

        return redirect()->route('whatsapp-accounts.index')
            ->with('success', 'Cuenta de WhatsApp eliminada correctamente.');
    }

    /**
     * @return array{valid: bool, error?: string, display_phone_number?: string}
     */
    private function verifyCredentials(string $phoneNumberId, string $accessToken): array
    {
        /** @var HttpResponse $response */
        $response = Http::withToken($accessToken)
            ->get("https://graph.facebook.com/v21.0/{$phoneNumberId}");

        if (! $response->successful()) {
            return [
                'valid' => false,
                'error' => $response->json('error.message', 'Unknown error'),
            ];
        }

        return [
            'valid' => true,
            'display_phone_number' => $response->json('display_phone_number'),
        ];
    }
}
