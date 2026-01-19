<?php

declare(strict_types=1);

namespace App\Services\Automation\NodeRunners;

use App\Models\WhatsappAccount;
use App\Services\Automation\Support\AutomationContext;
use App\Services\Automation\Support\NodeResult;
use App\Services\Automation\Support\TemplateRenderer;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

final class WhatsappMessageNodeRunner implements AutomationNodeRunner
{
    private const string API_VERSION = 'v21.0';

    private const string API_BASE = 'https://graph.facebook.com';

    public function type(): string
    {
        return 'whatsapp.send_message';
    }

    public function run(AutomationContext $context, array $config, array $input): NodeResult
    {
        $account = $this->resolveAccount($config);

        $action = $config['action'] ?? 'send_message';
        $to = $config['to'] ?? null;

        if (! is_string($to) || $to === '') {
            throw new InvalidArgumentException('WhatsApp node requires a "to" phone number.');
        }

        // Render template variables in the "to" field
        $templateData = $context->toTemplateData($input);
        $renderedTo = TemplateRenderer::renderString($to, $templateData);

        // Normalize phone number (remove + and spaces)
        $renderedTo = preg_replace('/[^0-9]/', '', $renderedTo) ?? $renderedTo;

        return match ($action) {
            'send_message' => $this->sendTextMessage($account, $renderedTo, $config, $templateData),
            'send_template' => $this->sendTemplateMessage($account, $renderedTo, $config, $templateData),
            default => throw new InvalidArgumentException("Unknown WhatsApp action: {$action}"),
        };
    }

    private function sendTextMessage(
        WhatsappAccount $account,
        string $to,
        array $config,
        array $templateData
    ): NodeResult {
        $messageTemplate = $config['message'] ?? '';
        if (! is_string($messageTemplate) || $messageTemplate === '') {
            throw new InvalidArgumentException('WhatsApp send_message requires a non-empty message.');
        }

        $renderedMessage = TemplateRenderer::renderString($messageTemplate, $templateData);
        $previewUrl = (bool) ($config['preview_url'] ?? false);

        $response = Http::withToken($account->access_token)
            ->post($this->getMessagesUrl($account), [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => $previewUrl,
                    'body' => $renderedMessage,
                ],
            ]);

        if (! $response->successful()) {
            return NodeResult::failure([
                'error' => $response->json('error.message', 'Failed to send message'),
                'code' => $response->json('error.code'),
                'status' => $response->status(),
            ]);
        }

        return NodeResult::success([
            'message_id' => $response->json('messages.0.id'),
            'to' => $to,
            'type' => 'text',
        ]);
    }

    private function sendTemplateMessage(
        WhatsappAccount $account,
        string $to,
        array $config,
        array $templateData
    ): NodeResult {
        $templateName = $config['template_name'] ?? '';
        if (! is_string($templateName) || $templateName === '') {
            throw new InvalidArgumentException('WhatsApp send_template requires a template_name.');
        }

        $languageCode = $config['template_language'] ?? 'es';

        // Build components from template_params (new UI format)
        $templateParams = $config['template_params'] ?? [];
        $components = $this->buildTemplateComponents($templateParams, $templateData);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ],
        ];

        if (! empty($components)) {
            $payload['template']['components'] = $components;
        }

        $response = Http::withToken($account->access_token)
            ->post($this->getMessagesUrl($account), $payload);

        if (! $response->successful()) {
            return NodeResult::failure([
                'error' => $response->json('error.message', 'Failed to send template'),
                'code' => $response->json('error.code'),
                'status' => $response->status(),
            ]);
        }

        return NodeResult::success([
            'message_id' => $response->json('messages.0.id'),
            'to' => $to,
            'type' => 'template',
            'template_name' => $templateName,
        ]);
    }

    /**
     * Build WhatsApp API template components from the template_params config.
     *
     * @param  array<string, string>  $templateParams  Keyed by param name, values are the content
     * @param  array<string, mixed>  $templateData
     * @return array<int, array<string, mixed>>
     */
    private function buildTemplateComponents(array $templateParams, array $templateData): array
    {
        if (empty($templateParams)) {
            return [];
        }

        $bodyParams = [];
        $buttonParams = [];

        foreach ($templateParams as $paramName => $value) {
            // Render the value with template variables
            $renderedValue = TemplateRenderer::renderString((string) $value, $templateData);

            // Check if it's a button parameter (format: button_0_url_1)
            if (str_starts_with($paramName, 'button_')) {
                preg_match('/button_(\d+)_url_(\d+)/', $paramName, $matches);
                if ($matches) {
                    $buttonIndex = (int) $matches[1];
                    if (! isset($buttonParams[$buttonIndex])) {
                        $buttonParams[$buttonIndex] = [];
                    }
                    $buttonParams[$buttonIndex][] = [
                        'type' => 'text',
                        'text' => $renderedValue,
                    ];
                }
            } else {
                // Body/header parameter
                $bodyParams[] = [
                    'type' => 'text',
                    'parameter_name' => $paramName,
                    'text' => $renderedValue,
                ];
            }
        }

        $components = [];

        // Add body component if there are body params
        if (! empty($bodyParams)) {
            $components[] = [
                'type' => 'body',
                'parameters' => $bodyParams,
            ];
        }

        // Add button components
        foreach ($buttonParams as $buttonIndex => $params) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => (string) $buttonIndex,
                'parameters' => $params,
            ];
        }

        return $components;
    }

    private function resolveAccount(array $config): WhatsappAccount
    {
        $accountId = $config['whatsapp_account_id'] ?? null;

        if (! is_string($accountId) || $accountId === '') {
            throw new InvalidArgumentException('WhatsApp node requires a whatsapp_account_id.');
        }

        $account = WhatsappAccount::query()->find($accountId);

        if (! $account instanceof WhatsappAccount) {
            throw new InvalidArgumentException("WhatsApp account not found: {$accountId}");
        }

        if (! $account->is_active) {
            throw new InvalidArgumentException('WhatsApp account is inactive.');
        }

        return $account;
    }

    private function getMessagesUrl(WhatsappAccount $account): string
    {
        return sprintf(
            '%s/%s/%s/messages',
            self::API_BASE,
            self::API_VERSION,
            $account->phone_number_id
        );
    }
}
