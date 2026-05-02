<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\EasyFactuException;
use App\Models\CompanyDetail;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final class EasyFactuService
{
    private const DEFAULT_BASE_URL = 'https://app.easyfactu.com/api';

    private const TIMEOUT_SECONDS = 30;

    /**
     * Check if EasyFactu is configured for the current tenant.
     */
    public function isConfigured(): bool
    {
        $environment = $this->getEnvironment();

        if (! $environment) {
            return false;
        }

        $apiKey = $this->getApiKeyForEnvironment($environment);

        return $apiKey !== '';
    }

    /**
     * Get the active DGII environment.
     */
    public function getEnvironment(): string
    {
        return CompanyDetail::getByKey('easyfactu_environment');
    }

    /**
     * Create a draft invoice in EasyFactu.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws EasyFactuException
     */
    public function createDraftInvoice(array $payload): array
    {
        $payload['draft'] = true;

        $response = $this->post('/v1/invoices', $payload, [
            'Idempotency-Key' => Str::ulid()->toBase32(),
        ]);

        return $this->parseResponse($response, 'Error creando borrador en EasyFactu.');
    }

    /**
     * Update a draft invoice in EasyFactu.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws EasyFactuException
     */
    public function updateDraftInvoice(string $invoiceId, array $payload): array
    {
        $response = $this->put("/v1/invoices/{$invoiceId}", $payload);

        return $this->parseResponse($response, 'Error actualizando borrador en EasyFactu.');
    }

    /**
     * Submit a draft invoice to DGII via EasyFactu.
     *
     * @return array<string, mixed>
     *
     * @throws EasyFactuException
     */
    public function submitInvoice(string $invoiceId): array
    {
        $response = $this->post(
            "/v1/invoices/{$invoiceId}/submit",
            retryConnectionIssues: true,
        );

        return $this->parseResponse($response, 'Error emitiendo factura en EasyFactu.');
    }

    /**
     * Get a single invoice from EasyFactu.
     *
     * @return array<string, mixed>
     *
     * @throws EasyFactuException
     */
    public function getInvoice(string $invoiceId): array
    {
        $response = $this->get("/v1/invoices/{$invoiceId}");

        return $this->parseResponse($response, 'Error obteniendo factura de EasyFactu.');
    }

    /**
     * Refresh DGII status for an invoice.
     *
     * @return array<string, mixed>
     *
     * @throws EasyFactuException
     */
    public function getInvoiceStatus(string $invoiceId): array
    {
        $response = $this->get("/v1/invoices/{$invoiceId}/status");

        return $this->parseResponse($response, 'Error consultando estado DGII.');
    }

    /**
     * Get the next available eNCF sequence for the given e-CF type.
     *
     * @return array<string, mixed>
     *
     * @throws EasyFactuException
     */
    public function getNextSequence(string $ecfType): array
    {
        $response = $this->get('/v1/sequences/next', [
            'ecf_type' => $ecfType,
        ]);

        return $this->parseResponse($response, 'Error obteniendo secuencia de EasyFactu.');
    }

    /**
     * Get received documents from EasyFactu.
     *
     * @return array<string, mixed>
     *
     * @throws EasyFactuException
     */
    public function getReceivedDocuments(): array
    {
        $response = $this->get('/v1/received-documents');

        return $this->parseResponse($response, 'Error obteniendo documentos recibidos de EasyFactu.');
    }

    /**
     * Get received documents from EasyFactu with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     *
     * @throws EasyFactuException
     */
    public function getReceivedDocumentsWithFilters(array $filters = []): array
    {
        $response = $this->get('/v1/received-documents', $filters);

        return $this->parseResponse($response, 'Error obteniendo documentos recibidos de EasyFactu.');
    }

    /**
     * Get a single received document from EasyFactu.
     *
     * @return array<string, mixed>
     *
     * @throws EasyFactuException
     */
    public function getReceivedDocument(string $receivedDocumentId): array
    {
        $response = $this->get("/v1/received-documents/{$receivedDocumentId}");

        return $this->parseResponse($response, 'Error obteniendo detalle del documento recibido en EasyFactu.');
    }

    /**
     * Get suppliers from EasyFactu.
     *
     * @return array<string, mixed>
     *
     * @throws EasyFactuException
     */
    public function getSuppliers(): array
    {
        $response = $this->get('/v1/suppliers');

        return $this->parseResponse($response, 'Error obteniendo suplidores de EasyFactu.');
    }

    /**
     * Send a GET request.
     *
     * @param  array<string, mixed>  $query
     *
     * @throws EasyFactuException
     */
    private function get(string $path, array $query = []): Response
    {
        try {
            return Http::withHeaders($this->buildHeaders())
                ->timeout(self::TIMEOUT_SECONDS)
                ->get($this->baseUrl().$path, $query);
        } catch (ConnectionException $e) {
            throw new EasyFactuException('Error de conexión con EasyFactu: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Send a POST request.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $extraHeaders
     *
     * @throws EasyFactuException
     */
    private function post(string $path, array $data = [], array $extraHeaders = [], bool $retryConnectionIssues = false): Response
    {
        try {
            $request = Http::withHeaders([...$this->buildHeaders(), ...$extraHeaders])
                ->timeout(self::TIMEOUT_SECONDS);

            if ($retryConnectionIssues) {
                $request = $request->retry(
                    [1000, 3000],
                    static fn (Throwable $exception, PendingRequest $pendingRequest): bool => $exception instanceof ConnectionException,
                );
            }

            return $request->post($this->baseUrl().$path, $data);
        } catch (ConnectionException $e) {
            throw new EasyFactuException('Error de conexión con EasyFactu: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Send a PUT request.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws EasyFactuException
     */
    private function put(string $path, array $data = []): Response
    {
        try {
            return Http::withHeaders($this->buildHeaders())
                ->timeout(self::TIMEOUT_SECONDS)
                ->put($this->baseUrl().$path, $data);
        } catch (ConnectionException $e) {
            throw new EasyFactuException('Error de conexión con EasyFactu: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Parse the API response and throw on errors.
     *
     * @return array<string, mixed>
     *
     * @throws EasyFactuException
     */
    private function parseResponse(Response $response, string $fallbackMessage): array
    {
        $body = $response->json() ?? [];

        if ($response->successful()) {
            return $body;
        }

        $message = $body['message'] ?? $body['error'] ?? $fallbackMessage;

        throw new EasyFactuException(
            message: $message,
            code: $response->status(),
            errors: $body['errors'] ?? [],
        );
    }

    /**
     * Build the authorization and content headers.
     *
     * @return array<string, string>
     *
     * @throws EasyFactuException
     */
    private function buildHeaders(): array
    {
        $apiKey = $this->resolveApiKey();

        return [
            'Authorization' => "Bearer {$apiKey}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Resolve the API key for the active environment.
     *
     * @throws EasyFactuException
     */
    private function resolveApiKey(): string
    {
        $environment = $this->getEnvironment();

        if (! $environment) {
            throw new EasyFactuException('No se ha configurado el entorno de facturación electrónica.');
        }

        $apiKey = $this->getApiKeyForEnvironment($environment);

        if ($apiKey === '') {
            throw new EasyFactuException("No se ha configurado la clave API para el entorno {$environment}.");
        }

        return $apiKey;
    }

    /**
     * Get the API key for a specific environment.
     */
    private function getApiKeyForEnvironment(string $environment): string
    {
        $key = 'easyfactu_api_key_'.mb_strtolower($environment);

        return CompanyDetail::getByKey($key);
    }

    /**
     * Get the EasyFactu API base URL.
     */
    private function baseUrl(): string
    {
        $url = CompanyDetail::getByKey('easyfactu_base_url');

        if ($url === '') {
            return self::DEFAULT_BASE_URL;
        }

        $normalizedUrl = mb_rtrim($url, '/');
        $path = parse_url($normalizedUrl, PHP_URL_PATH) ?? '';

        if ($path === '' || $path === '/') {
            return $normalizedUrl.'/api';
        }

        return $normalizedUrl;
    }
}
