<?php

declare(strict_types=1);

use App\Enums\DocumentType;
use App\Enums\InvoiceStatus;
use App\Enums\Permission;
use App\Models\CompanyDetail;
use App\Models\Contact;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\PermissionFactory;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $workspace = Workspace::factory()->create();
    $this->user = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $this->workspace = $workspace;

    $permission = PermissionFactory::new()->create([
        'name' => Permission::InvoicesEdit->value,
        'guard_name' => 'web',
    ]);

    $this->user->givePermissionTo($permission);

    CompanyDetail::setByKey('easyfactu_environment', 'TesteCF');
    CompanyDetail::setByKey('easyfactu_api_key_testecf', 'ef_testecf_12345678901234567890123456789012');
    CompanyDetail::setByKey('easyfactu_base_url', 'https://api.easyfactu.test');
});

test('it emits an electronic invoice and stores dgii metadata', function (): void {
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

    $contact = Contact::factory()->customer()->forWorkspace($this->workspace)->create();
    $documentSubtype = DocumentSubtype::factory()->create([
        'type' => DocumentType::Invoice,
        'prefix' => 'E31',
        'is_electronic' => true,
    ]);

    $invoice = Invoice::factory()->create([
        'workspace_id' => $this->workspace->id,
        'contact_id' => $contact->id,
        'document_subtype_id' => $documentSubtype->id,
        'status' => InvoiceStatus::Draft,
        'is_electronic' => true,
        'easyfactu_invoice_id' => 'ef_inv_1',
        'document_number' => 'E310000000100',
    ]);

    $response = $this->actingAs($this->user)->post(route('invoices.emit', $invoice));

    $response->assertRedirect();
    $response->assertSessionHas('success');

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
