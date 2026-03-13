<?php

declare(strict_types=1);

use App\Actions\SetWorkspacePreferredDocumentSubtypeAction;
use App\Enums\DocumentType;
use App\Models\DocumentSubtype;
use App\Models\Workspace;

test('workspace can keep one preferred subtype per document type', function (): void {
    $workspace = Workspace::factory()->create();

    $invoicePreferred = DocumentSubtype::factory()->create([
        'type' => DocumentType::Invoice,
        'prefix' => 'INV01',
    ]);
    $invoiceOther = DocumentSubtype::factory()->create([
        'type' => DocumentType::Invoice,
        'prefix' => 'INV02',
    ]);
    $paymentPreferred = DocumentSubtype::factory()->create([
        'type' => DocumentType::Payment,
        'prefix' => 'PAY01',
    ]);

    $workspace->documentSubtypes()->sync([
        $invoicePreferred->id => ['is_preferred' => true],
        $invoiceOther->id => ['is_preferred' => false],
        $paymentPreferred->id => ['is_preferred' => true],
    ]);

    expect($workspace->getPreferredDocumentSubtype(DocumentType::Invoice)?->is($invoicePreferred))->toBeTrue()
        ->and($workspace->getPreferredDocumentSubtype(DocumentType::Payment)?->is($paymentPreferred))->toBeTrue();
});

test('setting a preferred subtype only resets preferences of the same type', function (): void {
    $workspace = Workspace::factory()->create();

    $invoicePreferred = DocumentSubtype::factory()->create([
        'type' => DocumentType::Invoice,
        'prefix' => 'INV11',
    ]);
    $invoiceReplacement = DocumentSubtype::factory()->create([
        'type' => DocumentType::Invoice,
        'prefix' => 'INV12',
    ]);
    $paymentPreferred = DocumentSubtype::factory()->create([
        'type' => DocumentType::Payment,
        'prefix' => 'PAY11',
    ]);

    $workspace->documentSubtypes()->sync([
        $invoicePreferred->id => ['is_preferred' => true],
        $invoiceReplacement->id => ['is_preferred' => false],
        $paymentPreferred->id => ['is_preferred' => true],
    ]);

    app(SetWorkspacePreferredDocumentSubtypeAction::class)->handle($workspace, $invoiceReplacement);

    expect($workspace->fresh()->getPreferredDocumentSubtype(DocumentType::Invoice)?->is($invoiceReplacement))->toBeTrue()
        ->and($workspace->fresh()->getPreferredDocumentSubtype(DocumentType::Payment)?->is($paymentPreferred))->toBeTrue();

    $workspace->load('documentSubtypes');

    $invoicePreferredPivot = $workspace->documentSubtypes->firstWhere('id', $invoicePreferred->id)?->pivot;
    $paymentPreferredPivot = $workspace->documentSubtypes->firstWhere('id', $paymentPreferred->id)?->pivot;

    expect($invoicePreferredPivot?->is_preferred)->toBeFalse()
        ->and($paymentPreferredPivot?->is_preferred)->toBeTrue();
});
