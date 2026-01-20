<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

final class ListWhatsappAccountTemplatesController
{
    /**
     * Fetch message templates for a given account.
     */
    public function __invoke(WhatsappAccount $whatsappAccount): JsonResponse
    {
        if (empty($whatsappAccount->business_account_id)) {
            return response()->json([
                'error' => 'Business Account ID is required to fetch templates.',
                'templates' => [],
            ], 400);
        }

        /** @var Response $response */
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
}
