<?php

declare(strict_types=1);

namespace App\Services\Automation\NodeRunners;

use App\Services\Automation\Support\AutomationContext;
use App\Services\Automation\Support\NodeResult;
use App\Services\Automation\Support\TemplateRenderer;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

final class HttpWebhookNodeRunner implements AutomationNodeRunner
{
    public function type(): string
    {
        return 'http.webhook';
    }

    public function run(AutomationContext $context, array $config, array $input): NodeResult
    {
        $url = $config['url'] ?? null;
        if (! is_string($url) || $url === '') {
            throw new InvalidArgumentException('Webhook node requires a non-empty url.');
        }

        $method = mb_strtoupper((string) ($config['method'] ?? 'POST'));

        /** @var array<string, string> $headers */
        $headers = is_array($config['headers'] ?? null) ? $config['headers'] : [];

        $payload = $config['body'] ?? [];

        $rendered = TemplateRenderer::render($payload, $context->toTemplateData($input));

        $request = Http::withHeaders($headers);

        /** @var Response $response */
        $response = match ($method) {
            'POST' => $request->post($url, is_array($rendered) ? $rendered : ['body' => $rendered]),
            'PUT' => $request->put($url, is_array($rendered) ? $rendered : ['body' => $rendered]),
            'PATCH' => $request->patch($url, is_array($rendered) ? $rendered : ['body' => $rendered]),
            'DELETE' => $request->delete($url, is_array($rendered) ? $rendered : ['body' => $rendered]),
            default => throw new InvalidArgumentException("Unsupported webhook method [$method]."),
        };

        return NodeResult::success([
            'status' => $response->status(),
            'ok' => $response->successful(),
        ]);
    }
}
