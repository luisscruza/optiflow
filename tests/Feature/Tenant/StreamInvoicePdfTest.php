<?php

declare(strict_types=1);

use App\Models\CompanyDetail;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\Workspace;

test('invoice pdf streams successfully with all sections', function (): void {
    $user = User::factory()->create();

    CompanyDetail::setByKey('company_name', 'Optica Test');
    CompanyDetail::setByKey('tax_id', '123456789');
    CompanyDetail::setByKey('phone', '8091234567');
    CompanyDetail::setByKey('address', 'Santo Domingo, DN');
    CompanyDetail::setByKey('email', 'info@opticatest.com');

    $workspace = Workspace::factory()->create([
        'name' => 'Sucursal Central',
        'address' => 'Ave. Principal 123',
        'phone' => '8090000000',
    ]);

    $invoice = Invoice::factory()->create([
        'workspace_id' => $workspace->id,
        'notes' => 'Test note for invoice',
        'payment_term' => 'Se debe abonar el 50% del monto total.',
    ]);

    InvoiceItem::factory()->count(2)->create([
        'invoice_id' => $invoice->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('invoices.pdf-stream', $invoice));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});

test('invoice pdf displays company details dynamically', function (): void {
    $user = User::factory()->create();

    CompanyDetail::setByKey('company_name', 'Mi Optica RD');
    CompanyDetail::setByKey('tax_id', '987654321');

    $invoice = Invoice::factory()->create();

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('invoices.pdf-stream', $invoice));

    $response->assertSuccessful();
});

test('invoice pdf renders without optional data', function (): void {
    $user = User::factory()->create();

    $invoice = Invoice::factory()->create([
        'notes' => null,
        'payment_term' => null,
        'due_date' => null,
        'discount_amount' => 0,
    ]);

    InvoiceItem::factory()->withoutDiscount()->create([
        'invoice_id' => $invoice->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('invoices.pdf-stream', $invoice));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});
