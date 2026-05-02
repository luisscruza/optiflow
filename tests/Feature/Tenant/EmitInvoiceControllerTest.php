<?php

declare(strict_types=1);

use App\Enums\DocumentType;
use App\Enums\InvoiceStatus;
use App\Enums\Permission;
use App\Jobs\SubmitInvoiceToEasyFactuJob;
use App\Models\CompanyDetail;
use App\Models\Contact;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\PermissionFactory;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

function createEmitInvoiceTestContext(): array
{
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['current_workspace_id' => $workspace->id]);

    $permission = PermissionFactory::new()->create([
        'name' => Permission::InvoicesEdit->value,
        'guard_name' => 'web',
    ]);

    $user->givePermissionTo($permission);

    CompanyDetail::setByKey('easyfactu_environment', 'TesteCF');
    CompanyDetail::setByKey('easyfactu_api_key_testecf', 'ef_testecf_12345678901234567890123456789012');
    CompanyDetail::setByKey('easyfactu_base_url', 'https://api.easyfactu.test');

    return [$user, $workspace];
}

test('it queues electronic invoice submission and marks it as pending', function (): void {
    [$user, $workspace] = createEmitInvoiceTestContext();

    Queue::fake();

    $contact = Contact::factory()->customer()->forWorkspace($workspace)->create();
    $documentSubtype = DocumentSubtype::factory()->create([
        'type' => DocumentType::Invoice,
        'prefix' => 'E31',
        'is_electronic' => true,
    ]);

    $invoice = Invoice::factory()->create([
        'workspace_id' => $workspace->id,
        'contact_id' => $contact->id,
        'document_subtype_id' => $documentSubtype->id,
        'status' => InvoiceStatus::Draft,
        'is_electronic' => true,
        'easyfactu_invoice_id' => 'ef_inv_1',
        'document_number' => 'E310000000100',
    ]);

    $response = actingAs($user)->post(route('invoices.emit', $invoice));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Factura en proceso de envío a la DGII. Reintentaremos automáticamente si el servicio tarda en responder.');

    Queue::assertPushed(SubmitInvoiceToEasyFactuJob::class, function (SubmitInvoiceToEasyFactuJob $job) use ($invoice): bool {
        return $job->invoiceId === $invoice->id;
    });

    $invoice = $invoice->fresh();

    expect($invoice->status)->toBe(InvoiceStatus::Submitted)
        ->and($invoice->dgii_status)->toBe('Pendiente de envío a DGII');
});
