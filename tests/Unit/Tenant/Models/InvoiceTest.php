<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

test('to array', function (): void {
    $invoice = Invoice::factory()->create()->refresh();

    expect($invoice->toArray())
        ->toHaveKeys([
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
            'amount_paid',
            'human_readable_issue_date',
            'easyfactu_invoice_id',
            'encf',
            'dgii_status',
            'dgii_track_id',
            'dgii_security_code',
            'dgii_qr_code_url',
            'dgii_signed_at',
            'dgii_environment',
            'is_electronic',
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

test('electronic invoice helpers respect dgii workflow', function (): void {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::Draft,
        'is_electronic' => true,
        'easyfactu_invoice_id' => 'ef_inv_123',
    ]);

    expect($invoice->isElectronic())->toBeTrue()
        ->and($invoice->isDraft())->toBeTrue()
        ->and($invoice->canBeEmitted())->toBeTrue();

    $invoice->update(['status' => InvoiceStatus::Submitted]);

    expect($invoice->fresh()?->canBeEdited())->toBeFalse()
        ->and($invoice->fresh()?->canRegisterPayment())->toBeFalse();

    $invoice->update(['status' => InvoiceStatus::DgiiRejected]);

    expect($invoice->fresh()?->canBeEdited())->toBeFalse()
        ->and($invoice->fresh()?->canRegisterPayment())->toBeFalse();
});
