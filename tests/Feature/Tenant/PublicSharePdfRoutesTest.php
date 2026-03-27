<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Prescription;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Support\Facades\URL;

test('shared invoice pdf route streams with a valid signature', function (): void {
    $invoice = Invoice::factory()->create();

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
    ]);

    $url = URL::temporarySignedRoute('shared.invoices.pdf', now()->addDays(30), ['invoice' => $invoice]);

    $response = $this->get($url);

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});

test('shared quotation pdf route streams with a valid signature', function (): void {
    $quotation = Quotation::factory()->create();

    QuotationItem::factory()->create([
        'quotation_id' => $quotation->id,
    ]);

    $url = URL::temporarySignedRoute('shared.quotations.pdf', now()->addDays(30), ['quotation' => $quotation]);

    $response = $this->get($url);

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});

test('shared prescription pdf route streams with a valid signature', function (): void {
    $prescription = Prescription::factory()->create();

    $url = URL::temporarySignedRoute('shared.prescriptions.pdf', now()->addDays(30), ['prescription' => $prescription]);

    $response = $this->get($url);

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});

test('shared pdf routes reject invalid signatures', function (): void {
    $invoice = Invoice::factory()->create();

    $response = $this->get(route('shared.invoices.pdf', $invoice));

    $response->assertForbidden();
});
