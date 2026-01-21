<?php

declare(strict_types=1);

use Database\Factories\QuotationFactory;

test('to array', function (): void {
    $quotation = QuotationFactory::new()->create()->refresh();

    expect(array_keys($quotation->toArray()))->toBe([
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
    ]);
});
