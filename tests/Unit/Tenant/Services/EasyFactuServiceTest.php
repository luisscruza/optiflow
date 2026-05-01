<?php

declare(strict_types=1);

use App\Exceptions\EasyFactuException;
use App\Models\CompanyDetail;
use App\Services\EasyFactuService;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    CompanyDetail::setByKey('easyfactu_environment', 'TesteCF');
    CompanyDetail::setByKey('easyfactu_api_key_testecf', 'ef_testecf_12345678901234567890123456789012');
    CompanyDetail::setByKey('easyfactu_base_url', 'https://api.easyfactu.test');
});

test('it knows when easyfactu is configured', function (): void {
    $service = app(EasyFactuService::class);

    expect($service->isConfigured())->toBeTrue();

    CompanyDetail::setByKey('easyfactu_api_key_testecf', '');

    expect($service->isConfigured())->toBeFalse();
});

test('it creates draft invoices with auth and idempotency headers', function (): void {
    Http::fake([
        'https://api.easyfactu.test/v1/invoices' => Http::response([
            'invoice' => [
                'id' => 'ef_inv_1',
                'encf' => 'E310000000001',
            ],
        ], 201),
    ]);

    $response = app(EasyFactuService::class)->createDraftInvoice([
        'ecf_type' => '31',
        'issue_date' => '2026-04-06',
        'items' => [],
    ]);

    expect($response['invoice']['id'])->toBe('ef_inv_1');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.easyfactu.test/v1/invoices'
            && $request['draft'] === true
            && $request->hasHeader('Authorization', 'Bearer ef_testecf_12345678901234567890123456789012')
            && $request->hasHeader('Idempotency-Key')
            && $request->header('Idempotency-Key')[0] !== '';
    });
});

test('it throws a typed exception on api error', function (): void {
    Http::fake([
        'https://api.easyfactu.test/v1/sequences/next*' => Http::response([
            'message' => 'Clave inválida',
        ], 422),
    ]);

    expect(fn () => app(EasyFactuService::class)->getNextSequence('31'))
        ->toThrow(EasyFactuException::class, 'Clave inválida');
});

test('it fetches received documents from easyfactu', function (): void {
    Http::fake([
        'https://api.easyfactu.test/v1/received-documents' => Http::response([
            'documents' => [
                ['id' => 'rd_1', 'encf' => 'E310000000001'],
            ],
        ], 200),
    ]);

    $response = app(EasyFactuService::class)->getReceivedDocuments();

    expect($response['documents'])->toHaveCount(1)
        ->and($response['documents'][0]['id'])->toBe('rd_1');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.easyfactu.test/v1/received-documents'
            && $request->hasHeader('Authorization', 'Bearer ef_testecf_12345678901234567890123456789012');
    });
});

test('it fetches a received document detail from easyfactu', function (): void {
    Http::fake([
        'https://api.easyfactu.test/v1/received-documents/rd_1' => Http::response([
            'document' => ['id' => 'rd_1', 'encf' => 'E310000000001'],
        ], 200),
    ]);

    $response = app(EasyFactuService::class)->getReceivedDocument('rd_1');

    expect($response['document']['id'])->toBe('rd_1')
        ->and($response['document']['encf'])->toBe('E310000000001');
});
