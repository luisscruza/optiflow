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
        ->toContain('amount_due');
});