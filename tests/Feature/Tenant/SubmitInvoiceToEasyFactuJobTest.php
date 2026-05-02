<?php

declare(strict_types=1);

use App\Enums\DocumentType;
use App\Enums\InvoiceStatus;
use App\Exceptions\EasyFactuException;
use App\Jobs\SubmitInvoiceToEasyFactuJob;
use App\Models\CompanyDetail;
use App\Models\Contact;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\Workspace;
use App\Services\EasyFactuService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function createSubmitInvoiceWorkspace(): Workspace
{
    $workspace = Workspace::factory()->create();

    CompanyDetail::setByKey('easyfactu_environment', 'TesteCF');
    CompanyDetail::setByKey('easyfactu_api_key_testecf', 'ef_testecf_12345678901234567890123456789012');
    CompanyDetail::setByKey('easyfactu_base_url', 'https://api.easyfactu.test');

    return $workspace;
}

function createElectronicInvoice(Workspace $workspace): Invoice
{
    $contact = Contact::factory()->customer()->forWorkspace($workspace)->create();
    $documentSubtype = DocumentSubtype::factory()->create([
        'type' => DocumentType::Invoice,
        'prefix' => 'E31',
        'is_electronic' => true,
    ]);

    return Invoice::factory()->create([
        'workspace_id' => $workspace->id,
        'contact_id' => $contact->id,
        'document_subtype_id' => $documentSubtype->id,
        'status' => InvoiceStatus::Submitted,
        'is_electronic' => true,
        'easyfactu_invoice_id' => 'ef_inv_1',
        'document_number' => 'E310000000100',
        'dgii_status' => 'Pendiente de envío a DGII',
    ]);
}

test('it updates invoice metadata when the queued easyfactu submission succeeds', function (): void {
    $workspace = createSubmitInvoiceWorkspace();

    Http::fake([
        'https://api.easyfactu.test/v1/invoices/ef_inv_1/submit' => Http::response([
            'invoice' => [
                'status' => 'submitted',
                'dgii_status' => 'pending',
                'dgii_track_id' => 'track-123',
                'security_code' => 'sec-123',
                'qr_code_url' => 'https://qr.test/code.png',
                'signed_at' => '2026-04-06 09:08:27',
                'encf' => 'E310000000123',
            ],
        ], 200),
    ]);

    $invoice = createElectronicInvoice($workspace);

    $job = new SubmitInvoiceToEasyFactuJob($invoice->id);
    $job->handle(app(EasyFactuService::class));

    $invoice = $invoice->fresh();

    expect($invoice->status)->toBe(InvoiceStatus::Submitted)
        ->and($invoice->dgii_status)->toBe('pending')
        ->and($invoice->dgii_track_id)->toBe('track-123')
        ->and($invoice->dgii_security_code)->toBe('sec-123')
        ->and($invoice->dgii_qr_code_url)->toBe('https://qr.test/code.png')
        ->and($invoice->dgii_signed_at?->format('Y-m-d H:i:s'))->toBe('2026-04-06 09:08:27')
        ->and($invoice->encf)->toBe('E310000000123')
        ->and($invoice->document_number)->toBe('E310000000123');
});

test('it retries connection failures and restores the invoice to draft when retries are exhausted', function (): void {
    $workspace = createSubmitInvoiceWorkspace();

    Http::fake(static function (): never {
        throw new ConnectionException('Operation timed out after 30002 milliseconds with 0 bytes received');
    });

    $invoice = createElectronicInvoice($workspace);
    $job = new SubmitInvoiceToEasyFactuJob($invoice->id);

    $exception = null;

    try {
        $job->handle(app(EasyFactuService::class));
    } catch (EasyFactuException $caughtException) {
        $exception = $caughtException;
    }

    expect($exception)->toBeInstanceOf(EasyFactuException::class);

    $job->failed($exception);

    $invoice = $invoice->fresh();

    expect($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and($invoice->dgii_status)->toContain('Error sincronización: Error de conexión con EasyFactu');
});

test('it does not retry validation or business errors from easyfactu', function (): void {
    $workspace = createSubmitInvoiceWorkspace();

    Http::fake([
        'https://api.easyfactu.test/v1/invoices/ef_inv_1/submit' => Http::response([
            'message' => 'Factura inválida.',
            'errors' => ['invoice' => ['Factura inválida.']],
        ], 422),
    ]);

    $invoice = createElectronicInvoice($workspace);
    $job = new SubmitInvoiceToEasyFactuJob($invoice->id);

    $job->handle(app(EasyFactuService::class));

    $invoice = $invoice->fresh();

    expect($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and($invoice->dgii_status)->toBe('Error sincronización: Factura inválida.');
});

test('it restores the invoice to draft when easyfactu returns an error status in a successful response', function (): void {
    $workspace = createSubmitInvoiceWorkspace();

    Http::fake([
        'https://api.easyfactu.test/v1/invoices/ef_inv_1/submit' => Http::response([
            'invoice' => [
                'status' => 'error',
                'dgii_status' => 'Summary submission failed but DGII already has a final status: Aceptado',
                'encf' => 'E320000000008',
            ],
        ], 200),
    ]);

    $invoice = createElectronicInvoice($workspace);
    $job = new SubmitInvoiceToEasyFactuJob($invoice->id);

    $job->handle(app(EasyFactuService::class));

    $invoice = $invoice->fresh();

    expect($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and($invoice->dgii_status)->toBe('Summary submission failed but DGII already has a final status: Aceptado')
        ->and($invoice->encf)->toBe('E320000000008');
});
