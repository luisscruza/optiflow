<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Contact;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\Workspace;

test('to array', function (): void {
    $invoice = Invoice::factory()->create()->refresh();

    expect(array_keys($invoice->toArray()))
        ->toContain('id')
        ->toContain('workspace_id')
        ->toContain('contact_id')
        ->toContain('document_subtype_id')
        ->toContain('status')
        ->toContain('document_number')
        ->toContain('issue_date')
        ->toContain('due_date')
        ->toContain('total_amount')
        ->toContain('notes')
        ->toContain('created_at')
        ->toContain('updated_at')
        ->toContain('amount_due')
        ->toContain('status_config');
});

test('belongs to workspace', function (): void {
    $workspace = Workspace::factory()->create();
    $invoice = Invoice::factory()->create(['workspace_id' => $workspace->id]);

    expect($invoice->workspace)->toBeInstanceOf(Workspace::class);
    expect($invoice->workspace->id)->toBe($workspace->id);
});

test('belongs to contact', function (): void {
    $contact = Contact::factory()->create();
    $invoice = Invoice::factory()->create(['contact_id' => $contact->id]);

    expect($invoice->contact)->toBeInstanceOf(Contact::class);
    expect($invoice->contact->id)->toBe($contact->id);
});

test('belongs to document subtype', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create();
    $invoice = Invoice::factory()->create(['document_subtype_id' => $documentSubtype->id]);

    expect($invoice->documentSubtype)->toBeInstanceOf(DocumentSubtype::class);
    expect($invoice->documentSubtype->id)->toBe($documentSubtype->id);
});

test('has many items', function (): void {
    $invoice = Invoice::factory()->create();
    InvoiceItem::factory()->count(3)->create(['invoice_id' => $invoice->id]);

    expect($invoice->items)->toHaveCount(3);
    expect($invoice->items->first())->toBeInstanceOf(InvoiceItem::class);
});

test('has many stock movements', function (): void {
    $invoice = Invoice::factory()->create();
    StockMovement::factory()->count(2)->create(['related_invoice_id' => $invoice->id]);

    expect($invoice->stockMovements)->toHaveCount(2);
    expect($invoice->stockMovements->first())->toBeInstanceOf(StockMovement::class);
});

test('has many payments', function (): void {
    $invoice = Invoice::factory()->create();
    Payment::factory()->count(2)->create(['invoice_id' => $invoice->id]);

    expect($invoice->payments)->toHaveCount(2);
    expect($invoice->payments->first())->toBeInstanceOf(Payment::class);
});

test('recalculate total updates total amount', function (): void {
    $invoice = Invoice::factory()->create(['total_amount' => 0]);
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'total' => 100,
    ]);
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'total' => 200,
    ]);

    $invoice->recalculateTotal();

    expect($invoice->fresh()->total_amount)->toBe('300.00');
});

test('recalculate total does not update if already correct', function (): void {
    $invoice = Invoice::factory()->create(['total_amount' => 300]);
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'total' => 100,
    ]);
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'total' => 200,
    ]);

    $originalUpdatedAt = $invoice->updated_at;
    $invoice->recalculateTotal();

    expect($invoice->fresh()->updated_at->timestamp)->toBe($originalUpdatedAt->timestamp);
});

test('status is paid when fully paid', function (): void {
    $invoice = Invoice::factory()->create(['total_amount' => 1000]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 1000,
    ]);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

test('status is partially paid when partially paid', function (): void {
    $invoice = Invoice::factory()->create(['total_amount' => 1000]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 500,
    ]);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PartiallyPaid);
});

test('status is pending payment when unpaid', function (): void {
    $invoice = Invoice::factory()->create(['total_amount' => 1000]);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PendingPayment);
});

test('status config accessor returns correct structure', function (): void {
    $invoice = Invoice::factory()->create(['total_amount' => 1000]);

    $statusConfig = $invoice->status_config;

    expect($statusConfig)->toBeArray();
    expect($statusConfig)->toHaveKeys(['value', 'label', 'variant', 'className']);
});

test('amount due calculates correctly with no payments', function (): void {
    $invoice = Invoice::factory()->create(['total_amount' => 1000]);

    expect($invoice->amount_due)->toBe(1000.0);
});

test('amount due calculates correctly with partial payment', function (): void {
    $invoice = Invoice::factory()->create(['total_amount' => 1000]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 300,
    ]);

    expect($invoice->fresh()->amount_due)->toBe(700.0);
});

test('amount due is zero when fully paid', function (): void {
    $invoice = Invoice::factory()->create(['total_amount' => 1000]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 1000,
    ]);

    expect($invoice->fresh()->amount_due)->toBe(0.0);
});

test('amount due never goes negative', function (): void {
    $invoice = Invoice::factory()->create(['total_amount' => 1000]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 1500,
    ]);

    expect($invoice->fresh()->amount_due)->toBe(0.0);
});

test('casts work correctly', function (): void {
    $invoice = Invoice::factory()->create([
        'issue_date' => '2025-01-15',
        'due_date' => '2025-02-15',
        'total_amount' => 1234.56,
    ]);

    expect($invoice->issue_date)->toBeInstanceOf(Carbon\CarbonImmutable::class);
    expect($invoice->due_date)->toBeInstanceOf(Carbon\CarbonImmutable::class);
    expect($invoice->total_amount)->toBeString();
    expect($invoice->total_amount)->toBe('1234.56');
});
