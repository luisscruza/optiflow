<?php

declare(strict_types=1);

use App\Models\Address;
use App\Models\Contact;

it('belongs to a contact', function (): void {
    $contact = Contact::factory()->create();
    $address = Address::factory()->create(['contact_id' => $contact->id]);

    expect($address->contact)->toBeInstanceOf(Contact::class);
    expect($address->contact->id)->toBe($contact->id);
});

it('can be marked as primary', function (): void {
    $contact = Contact::factory()->create();
    $address = Address::factory()->primary()->create(['contact_id' => $contact->id]);

    expect($address->is_primary)->toBeTrue();
});

it('generates full address from parts', function (): void {
    $address = Address::factory()->create([
        'description' => '123 Main Street',
        'municipality' => 'Springfield',
        'province' => 'IL',
        'country' => 'USA',
    ]);

    expect($address->full_address)->toBe('123 Main Street, Springfield, IL, USA');
});

it('handles partial address parts in full address', function (): void {
    $address = Address::factory()->create([
        'description' => '456 Oak Ave',
        'municipality' => null,
        'province' => 'CA',
        'country' => 'USA',
    ]);

    expect($address->full_address)->toBe('456 Oak Ave, CA, USA');
});

it('returns null for full address when all parts are empty', function (): void {
    $address = Address::factory()->create([
        'description' => null,
        'municipality' => null,
        'province' => null,
        'country' => null,
    ]);

    expect($address->full_address)->toBeNull();
});

it('can scope to primary addresses', function (): void {
    $contact = Contact::factory()->create();
    Address::factory()->create(['contact_id' => $contact->id, 'is_primary' => false]);
    Address::factory()->create(['contact_id' => $contact->id, 'is_primary' => false]);
    $primaryAddress = Address::factory()->create(['contact_id' => $contact->id, 'is_primary' => true]);

    $addresses = Address::primary()->get();

    expect($addresses)->toHaveCount(1);
    expect($addresses->first()->id)->toBe($primaryAddress->id);
});

it('casts is_primary to boolean', function (): void {
    $address = Address::factory()->create(['is_primary' => true]);

    expect($address->is_primary)->toBeBool();
    expect($address->is_primary)->toBeTrue();
});

it('includes full_address in appends', function (): void {
    $address = Address::factory()->create([
        'description' => '789 Elm St',
        'municipality' => 'Portland',
        'province' => 'OR',
        'country' => 'USA',
    ]);

    $array = $address->toArray();

    expect($array)->toHaveKey('full_address');
    expect($array['full_address'])->toBe('789 Elm St, Portland, OR, USA');
});
