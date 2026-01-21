<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

test('to array', function (): void {
    $invoice = Invoice::factory()->create()->refresh();

    expect(array_keys($invoice->toArray()))->toBe([
        'id',
        'workspace_id',
        'contact_id',
        'document_subtype_id',
        'status',
        'document_number',
        'issue_date',
        'due_date',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'subtotal_amount',
        'notes',
        'created_by',
        'currency_id',
        'created_at',
        'updated_at',
        'payment_term',
        'amount_due',
        'human_readable_issue_date',
    ]);
});

test('checks status helpers', function (): void {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::PendingPayment,
    ]);

    expect($invoice->canBeDeleted())->toBeTrue()
        ->and($invoice->canBeEdited())->toBeTrue()
        ->and($invoice->canRegisterPayment())->toBeTrue();

    $invoice->status = InvoiceStatus::Paid;
    $invoice->save();

    $invoice = $invoice->refresh();

    expect($invoice->canBeDeleted())->toBeFalse()
        ->and($invoice->canBeEdited())->toBeFalse()
        ->and($invoice->canRegisterPayment())->toBeFalse();
});
