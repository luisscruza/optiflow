<?php

declare(strict_types=1);

use App\Enums\Gender;
use App\Enums\IdentificationType;
use App\Models\Contact;
use Illuminate\Support\Facades\File;

test('creates a contact when no match exists', function (): void {
    $path = storage_path('app/update-contact-command-create.json');

    File::put($path, json_encode([
        [
            'id' => 1,
            'name' => 'Prueba Uno',
            'email' => 'cruzmediaorg@gmail.com',
            'f_phone' => '8095400003',
            's_phone' => null,
            'address' => 'CALLE 3, NO. 2',
            'sucursal' => 4,
            'birthdate' => '2023-02-27 00:00:00',
            'gender' => '1',
            'identificacion' => '40212945865',
            'created_at' => '2023-03-14 15:21:34',
            'updated_at' => '2023-03-14 15:21:34',
            'age' => null,
            'comment' => 'Cliente importado',
        ],
    ], JSON_THROW_ON_ERROR));

    $this->artisan('contacts:update-from-json', [
        'file' => $path,
    ])->assertSuccessful();

    $contact = Contact::query()->where('email', 'cruzmediaorg@gmail.com')->first();

    expect($contact)->not->toBeNull();
    expect($contact->name)->toBe('Prueba Uno');
    expect($contact->phone_primary)->toBe('8095400003');
    expect($contact->identification_number)->toBe('40212945865');
    expect($contact->identification_type)->toBe(IdentificationType::Cedula->value);
    expect($contact->gender)->toBe(Gender::Male);
    expect($contact->observations)->toBe('Cliente importado');
    expect($contact->metadata)->toMatchArray([
        'legacy_client_id' => 1,
        'legacy_branch_id' => 4,
    ]);

    File::delete($path);
});

test('updates only missing values for an existing contact matched by email', function (): void {
    $contact = Contact::factory()->customer()->create([
        'name' => 'Cliente Demo',
        'email' => 'cliente@example.com',
        'phone_primary' => '8091111111',
        'phone_secondary' => null,
        'identification_type' => null,
        'identification_number' => null,
        'observations' => null,
        'gender' => Gender::NotSpecified,
        'birth_date' => null,
        'metadata' => null,
    ]);

    $path = storage_path('app/update-contact-command-update.json');

    File::put($path, json_encode([
        [
            'id' => 9,
            'name' => 'Cliente Demo',
            'email' => 'cliente@example.com',
            'f_phone' => '8099999999',
            's_phone' => '8092222222',
            'sucursal' => 3,
            'birthdate' => '1999-05-01 00:00:00',
            'gender' => '2',
            'identificacion' => '40200000001',
            'comment' => 'Nota migrada',
        ],
    ], JSON_THROW_ON_ERROR));

    $this->artisan('contacts:update-from-json', [
        'file' => $path,
    ])->assertSuccessful();

    $contact->refresh();

    expect($contact->phone_primary)->toBe('8091111111');
    expect($contact->phone_secondary)->toBe('8092222222');
    expect($contact->identification_number)->toBe('40200000001');
    expect($contact->identification_type)->toBe(IdentificationType::Cedula->value);
    expect($contact->observations)->toBe('Nota migrada');
    expect($contact->gender)->toBe(Gender::Female);
    expect($contact->birth_date?->toDateString())->toBe('1999-05-01');
    expect($contact->metadata)->toMatchArray([
        'legacy_client_id' => 9,
        'legacy_branch_id' => 3,
    ]);

    File::delete($path);
});

test('matches an existing contact by phone before falling back to name', function (): void {
    $contact = Contact::factory()->customer()->create([
        'name' => 'Nombre Diferente',
        'email' => null,
        'phone_primary' => '(809) 333-4444',
        'phone_secondary' => null,
        'identification_number' => null,
        'identification_type' => null,
    ]);

    $path = storage_path('app/update-contact-command-phone.json');

    File::put($path, json_encode([
        [
            'id' => 10,
            'name' => 'Paciente Legacy',
            'email' => 'legacy@example.com',
            'f_phone' => '8093334444',
            'identificacion' => '40200000010',
        ],
    ], JSON_THROW_ON_ERROR));

    $this->artisan('contacts:update-from-json', [
        'file' => $path,
    ])->assertSuccessful();

    expect(Contact::query()->count())->toBe(1);

    $contact->refresh();

    expect($contact->email)->toBe('legacy@example.com');
    expect($contact->identification_number)->toBe('40200000010');

    File::delete($path);
});

test('skips ambiguous matches', function (): void {
    Contact::factory()->customer()->create([
        'name' => 'Juan Perez',
        'email' => null,
        'phone_primary' => null,
        'identification_number' => null,
    ]);

    Contact::factory()->customer()->create([
        'name' => 'juan   perez',
        'email' => null,
        'phone_primary' => null,
        'identification_number' => null,
    ]);

    $path = storage_path('app/update-contact-command-ambiguous.json');

    File::put($path, json_encode([
        [
            'id' => 11,
            'name' => 'Juan Perez',
            'email' => 'ambiguous@example.com',
            'f_phone' => '8090000000',
        ],
    ], JSON_THROW_ON_ERROR));

    $this->artisan('contacts:update-from-json', [
        'file' => $path,
        '--debug' => true,
    ])
        ->expectsOutputToContain('coincidencia ambigua')
        ->assertSuccessful();

    expect(Contact::query()->count())->toBe(2);
    expect(Contact::query()->where('email', 'ambiguous@example.com')->exists())->toBeFalse();

    File::delete($path);
});
