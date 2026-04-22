<?php

declare(strict_types=1);

use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\Quotation;
use Carbon\Carbon;

test('to array', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create()->refresh();

    expect(array_keys($documentSubtype->toArray()))->toBe([
        'id',
        'name',
        'type',
        'is_active',
        'is_default',
        'valid_until_date',
        'prefix',
        'start_number',
        'end_number',
        'next_number',
        'created_at',
        'updated_at',
    ]);
});

test('handles ncf sequence checks', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'next_number' => 1,
        'end_number' => 200,
        'valid_until_date' => Carbon::now()->addDays(10),
    ]);

    expect($documentSubtype->isValid())->toBeTrue()
        ->and($documentSubtype->generateNCF())->toBe('B0100000001');

    $next = $documentSubtype->getNextNcfNumber();
    $documentSubtype->refresh();

    expect($next)->toBe('B0100000001')
        ->and($documentSubtype->next_number)->toBe(2)
        ->and($documentSubtype->isRunningLow())->toBeFalse();

    $documentSubtype->update([
        'next_number' => 150,
        'end_number' => 200,
    ]);

    expect($documentSubtype->isRunningLow())->toBeTrue();

    $documentSubtype->update([
        'valid_until_date' => Carbon::now()->addDays(5),
    ]);

    expect($documentSubtype->isNearExpiration())->toBeTrue();
});

test('applies the active global scope by default', function (): void {
    $activeSubtype = DocumentSubtype::factory()->create(['name' => 'Active subtype']);
    $inactiveSubtype = DocumentSubtype::factory()->create([
        'name' => 'Inactive subtype',
        'is_active' => false,
    ]);

    expect(DocumentSubtype::query()->pluck('name')->all())
        ->toContain($activeSubtype->name)
        ->not->toContain($inactiveSubtype->name)
        ->and(DocumentSubtype::withoutGlobalScope('is_active')->pluck('name')->all())
        ->toContain($inactiveSubtype->name);
});

test('route binding can still resolve an inactive subtype', function (): void {
    $inactiveSubtype = DocumentSubtype::factory()->create([
        'is_active' => false,
    ]);

    $resolvedSubtype = (new DocumentSubtype)->resolveRouteBinding($inactiveSubtype->getKey());

    expect($resolvedSubtype)->not->toBeNull();
    expect($resolvedSubtype?->id)->toBe($inactiveSubtype->id);
});

test('existing invoices and quotations can still access an inactive subtype', function (): void {
    $inactiveSubtype = DocumentSubtype::factory()->create([
        'is_active' => false,
    ]);

    $invoice = Invoice::factory()->create([
        'document_subtype_id' => $inactiveSubtype->id,
    ]);

    $quotation = Quotation::factory()->create([
        'document_subtype_id' => $inactiveSubtype->id,
    ]);

    expect($invoice->fresh()->documentSubtype?->is($inactiveSubtype))->toBeTrue()
        ->and($quotation->fresh()->documentSubtype?->is($inactiveSubtype))->toBeTrue();
});
