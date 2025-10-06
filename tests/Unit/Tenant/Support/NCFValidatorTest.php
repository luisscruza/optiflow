<?php

declare(strict_types=1);

use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Support\NCFValidator;

it('validates a valid NCF', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'start_number' => 1,
        'end_number' => 1000,
        'next_number' => 1,
        'valid_until_date' => now()->addYear(),
    ]);

    $ncf = 'B0100000050';
    $data = ['issue_date' => now()->toDateString()];

    $result = NCFValidator::validate($ncf, $documentSubtype, $data);

    expect($result)->toBeTrue();
});

it('returns false when NCF already exists in invoices', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'start_number' => 1,
        'end_number' => 1000,
        'next_number' => 1,
    ]);

    $ncf = 'B0100000050';

    // Create an existing invoice with this document number
    Invoice::factory()->create(['document_number' => $ncf]);

    $data = ['issue_date' => now()->toDateString()];
    $result = NCFValidator::validate($ncf, $documentSubtype, $data);

    expect($result)->toBeFalse();
});

it('returns false when prefix does not match', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'start_number' => 1,
        'end_number' => 1000,
        'next_number' => 1,
    ]);

    $ncf = 'B0200000050'; // Wrong prefix
    $data = ['issue_date' => now()->toDateString()];

    $result = NCFValidator::validate($ncf, $documentSubtype, $data);

    expect($result)->toBeFalse();
});

it('returns false when issue date is after valid_until_date', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'start_number' => 1,
        'end_number' => 1000,
        'next_number' => 1,
        'valid_until_date' => now()->subDay(),
    ]);

    $ncf = 'B0100000050';
    $data = ['issue_date' => now()->toDateString()];

    $result = NCFValidator::validate($ncf, $documentSubtype, $data);

    expect($result)->toBeFalse();
});

it('returns true when valid_until_date is null', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'start_number' => 1,
        'end_number' => 1000,
        'next_number' => 1,
        'valid_until_date' => null,
    ]);

    $ncf = 'B0100000050';
    $data = ['issue_date' => now()->toDateString()];

    $result = NCFValidator::validate($ncf, $documentSubtype, $data);

    expect($result)->toBeTrue();
});

it('returns false when number exceeds end_number', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'start_number' => 1,
        'end_number' => 100,
        'next_number' => 1,
    ]);

    $ncf = 'B0100000150'; // Number 150 exceeds end_number 100
    $data = ['issue_date' => now()->toDateString()];

    $result = NCFValidator::validate($ncf, $documentSubtype, $data);

    expect($result)->toBeFalse();
});

it('returns true when end_number is null', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'start_number' => 1,
        'end_number' => null,
        'next_number' => 1,
    ]);

    $ncf = 'B0100001500'; // Large number, but end_number is null
    $data = ['issue_date' => now()->toDateString()];

    $result = NCFValidator::validate($ncf, $documentSubtype, $data);

    expect($result)->toBeTrue();
});

it('returns false when number is less than start_number', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'start_number' => 100,
        'end_number' => 1000,
        'next_number' => 100,
    ]);

    $ncf = 'B0100000050'; // Number 50 is less than start_number 100
    $data = ['issue_date' => now()->toDateString()];

    $result = NCFValidator::validate($ncf, $documentSubtype, $data);

    expect($result)->toBeFalse();
});

it('returns false when number is less than next_number', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'start_number' => 1,
        'end_number' => 1000,
        'next_number' => 100,
    ]);

    $ncf = 'B0100000050'; // Number 50 is less than next_number 100
    $data = ['issue_date' => now()->toDateString()];

    $result = NCFValidator::validate($ncf, $documentSubtype, $data);

    expect($result)->toBeFalse();
});

it('handles NCF with leading zeros correctly', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'start_number' => 1,
        'end_number' => 1000,
        'next_number' => 1,
    ]);

    $ncf = 'B0100000005'; // Number 5 with leading zeros
    $data = ['issue_date' => now()->toDateString()];

    $result = NCFValidator::validate($ncf, $documentSubtype, $data);

    expect($result)->toBeTrue();
});

it('validates when all conditions are met at boundary values', function (): void {
    $documentSubtype = DocumentSubtype::factory()->create([
        'prefix' => 'B01',
        'start_number' => 100,
        'end_number' => 200,
        'next_number' => 100,
        'valid_until_date' => now()->addDay(),
    ]);

    $ncf = 'B0100000100'; // Exactly at start_number
    $data = ['issue_date' => now()->toDateString()];

    $result = NCFValidator::validate($ncf, $documentSubtype, $data);

    expect($result)->toBeTrue();
});
