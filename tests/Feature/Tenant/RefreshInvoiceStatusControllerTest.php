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
        'name' => Permission::InvoicesView->value,
        'guard_name' => 'web',
    ]);

    $this->user->givePermissionTo($permission);

    CompanyDetail::setByKey('easyfactu_environment', 'TesteCF');
    CompanyDetail::setByKey('easyfactu_api_key_testecf', 'ef_testecf_12345678901234567890123456789012');
    CompanyDetail::setByKey('easyfactu_base_url', 'https://api.easyfactu.test');
});

test('it refreshes an accepted electronic invoice into pending payment', function (): void {
    Http::fake([
        'https://api.easyfactu.test/v1/invoices/ef_inv_1/status' => Http::response([
            'invoice' => [
                'status' => 'accepted',
                'dgii_status' => 'accepted',
                'dgii_track_id' => 'track-accepted',
                'security_code' => 'sec-accepted',
                'qr_code_url' => 'https://qr.test/accepted.png',
                'signed_at' => '2026-04-06 09:08:27',
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
        'status' => InvoiceStatus::Submitted,
        'is_electronic' => true,
        'easyfactu_invoice_id' => 'ef_inv_1',
        'document_number' => 'E310000000100',
    ]);

    $response = $this->actingAs($this->user)->post(route('invoices.refresh-status', $invoice));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $invoice = $invoice->fresh();

    expect($invoice->status)->toBe(InvoiceStatus::PendingPayment)
        ->and($invoice->dgii_status)->toBe('accepted')
        ->and($invoice->dgii_track_id)->toBe('track-accepted')
        ->and($invoice->dgii_security_code)->toBe('sec-accepted')
        ->and($invoice->dgii_signed_at?->format('Y-m-d H:i:s'))->toBe('2026-04-06 09:08:27')
        ->and($invoice->dgii_qr_code_url)->toBe('https://qr.test/accepted.png');
});
