<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use Illuminate\Http\JsonResponse;
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
     * List accounts for API/JSON consumption (automation builder).
     */
    public function list(): JsonResponse
    {
        $accounts = WhatsappAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'display_phone_number', 'business_account_id']);

        return response()->json($accounts);
    }

    /**
     * Fetch message templates for a given account.
     */
    public function templates(WhatsappAccount $whatsappAccount): JsonResponse
    {
        if (empty($whatsappAccount->business_account_id)) {
            return response()->json([
                'error' => 'Business Account ID is required to fetch templates.',
                'templates' => [],
            ], 400);
        }

        $response = Http::withToken($whatsappAccount->access_token)
            ->get("https://graph.facebook.com/v21.0/{$whatsappAccount->business_account_id}/message_templates", [
                'limit' => 100,
            ]);

        if (! $response->successful()) {
            return response()->json([
                'error' => $response->json('error.message', 'Failed to fetch templates'),
                'templates' => [],
            ], $response->status());
        }

        $templates = collect($response->json('data', []))
            ->filter(fn ($t) => ($t['status'] ?? '') === 'APPROVED')
            ->map(function ($t) {
                $parameters = $this->extractTemplateParameters($t['components'] ?? []);

                return [
                    'name' => $t['name'],
                    'language' => $t['language'],
                    'category' => $t['category'] ?? null,
                    'parameter_format' => $t['parameter_format'] ?? 'POSITIONAL',
                    'components' => $t['components'] ?? [],
                    'parameters' => $parameters,
                ];
            })
            ->values()
            ->all();

        return response()->json(['templates' => $templates]);
    }

    /**
     * Send a test message.
     */
    public function testMessage(Request $request, WhatsappAccount $whatsappAccount): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        $response = Http::withToken($whatsappAccount->access_token)
            ->post("https://graph.facebook.com/v21.0/{$whatsappAccount->phone_number_id}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $validated['to'],
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $validated['message'],
                ],
            ]);

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'error' => $response->json('error.message', 'Failed to send message'),
            ], $response->status());
        }

        return response()->json([
            'success' => true,
            'message_id' => $response->json('messages.0.id'),
        ]);
    }

    /**
     * Extract parameters from template components.
     *
     * @param  array<int, array<string, mixed>>  $components
     * @return array<int, array{name: string, type: string, component: string, example?: string}>
     */
    private function extractTemplateParameters(array $components): array
    {
        $parameters = [];

        foreach ($components as $component) {
            $componentType = $component['type'] ?? '';

            // Extract named parameters from BODY component
            if ($componentType === 'BODY' || $componentType === 'HEADER') {
                $namedParams = $component['example']['body_text_named_params'] ?? [];
                foreach ($namedParams as $param) {
                    $parameters[] = [
                        'name' => $param['param_name'],
                        'type' => 'text',
                        'component' => mb_strtolower($componentType),
                        'example' => $param['example'] ?? null,
                    ];
                }

                // Also check for positional parameters in the text ({{1}}, {{2}}, etc.)
                $text = $component['text'] ?? '';
                if (preg_match_all('/\{\{(\d+)\}\}/', $text, $matches)) {
                    foreach ($matches[1] as $num) {
                        // Only add if we don't already have named params
                        if (empty($namedParams)) {
                            $parameters[] = [
                                'name' => $num,
                                'type' => 'text',
                                'component' => mb_strtolower($componentType),
                                'positional' => true,
                            ];
                        }
                    }
                }
            }

            // Extract button URL parameters
            if ($componentType === 'BUTTONS') {
                $buttons = $component['buttons'] ?? [];
                foreach ($buttons as $buttonIndex => $button) {
                    if (($button['type'] ?? '') === 'URL') {
                        $url = $button['url'] ?? '';
                        if (preg_match_all('/\{\{(\d+)\}\}/', $url, $matches)) {
                            foreach ($matches[1] as $num) {
                                $parameters[] = [
                                    'name' => "button_{$buttonIndex}_url_{$num}",
                                    'type' => 'text',
                                    'component' => 'button',
                                    'button_index' => $buttonIndex,
                                    'button_text' => $button['text'] ?? 'Button',
                                    'example' => $button['example'][$num - 1] ?? null,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $parameters;
    }

    /**
     * @return array{valid: bool, error?: string, display_phone_number?: string}
     */
    private function verifyCredentials(string $phoneNumberId, string $accessToken): array
    {
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
