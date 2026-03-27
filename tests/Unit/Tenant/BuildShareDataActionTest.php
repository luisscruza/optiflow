<?php

declare(strict_types=1);

use App\Actions\BuildShareDataAction;
use App\Models\Invoice;
use App\Models\ShareTemplate;

test('it builds rendered share data for invoices', function (): void {
    $invoice = Invoice::factory()->create([
        'document_number' => 'B0100001',
    ]);

    /** @var BuildShareDataAction $action */
    $action = app(BuildShareDataAction::class);

    $payload = $action->forInvoice($invoice->load('contact', 'workspace'));

    expect($payload['entityType'])->toBe('invoice')
        ->and($payload['shareableLink'])->toContain('/shared/invoices/')
        ->and($payload['templates']['email'])->not->toBeNull()
        ->and($payload['templates']['email']['body'])->toContain((string) $invoice->contact->name)
        ->and(ShareTemplate::query()->count())->toBe(6);
});
