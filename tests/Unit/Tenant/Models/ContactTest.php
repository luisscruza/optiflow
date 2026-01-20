<?php

declare(strict_types=1);

use App\Models\Contact;

test('to array', function (): void {
    $contact = Contact::factory()->create()->refresh();

    expect(array_keys($contact->toArray()))->toBe([
        'id',
        'name',
        'email',
        'phone_primary',
        'phone_secondary',
        'mobile',
        'fax',
        'identification_type',
        'identification_number',
        'contact_type',
        'status',
        'observations',
        'credit_limit',
        'metadata',
        'created_at',
        'updated_at',
        'birth_date',
        'gender',
        'identification_object',
        'full_address',
        'primary_address',
    ]);
});
