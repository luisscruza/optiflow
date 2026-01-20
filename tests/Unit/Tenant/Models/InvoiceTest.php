<?php

declare(strict_types=1);

use App\Models\Invoice;

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
        ->toContain('amount_due');
});
