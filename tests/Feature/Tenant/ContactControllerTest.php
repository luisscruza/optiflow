<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Contact;
use App\Models\Mastertable;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'current_workspace_id' => $workspace->id,
        'password_changed_at' => now(),
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
});

test('edit page loads without error when contacts have appended attributes', function (): void {
    $permission = PermissionModel::findOrCreate(Permission::ContactsEdit->value, 'web');

    $this->user->givePermissionTo($permission);

    $contact = Contact::factory()->create();

    // Create additional active contacts that will appear in the relationship picker
    Contact::factory()->count(3)->create(['status' => 'active']);

    $response = $this->actingAs($this->user)
        ->get("/contacts/{$contact->id}/edit");

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('leadSourceOptions', 4)
        ->where('leadSourceOptions.0.label', 'Redes sociales'));
});

test('create page loads without error when contacts have appended attributes', function (): void {
    $permission = PermissionModel::findOrCreate(Permission::ContactsCreate->value, 'web');

    $this->user->givePermissionTo($permission);

    // Create active contacts that will appear in the relationship picker
    Contact::factory()->count(3)->create(['status' => 'active']);

    $response = $this->actingAs($this->user)
        ->get('/contacts/create');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('leadSourceOptions', 4)
        ->where('leadSourceOptions.0.label', 'Redes sociales'));
});

test('it stores the selected lead source when creating a contact', function (): void {
    $permission = PermissionModel::findOrCreate(Permission::ContactsCreate->value, 'web');

    $this->user->givePermissionTo($permission);

    $leadSourceId = Mastertable::query()
        ->where('alias', Contact::LEAD_SOURCES_MASTERTABLE_ALIAS)
        ->firstOrFail()
        ->items()
        ->orderBy('id')
        ->value('id');

    $response = $this->actingAs($this->user)->post('/contacts', [
        'name' => 'Contacto nuevo',
        'contact_type' => 'customer',
        'lead_source_id' => $leadSourceId,
        'status' => 'active',
        'gender' => 'male',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('contacts', [
        'name' => 'Contacto nuevo',
        'lead_source_id' => $leadSourceId,
    ]);
});

test('it updates the selected lead source for a contact', function (): void {
    $permission = PermissionModel::findOrCreate(Permission::ContactsEdit->value, 'web');

    $this->user->givePermissionTo($permission);

    $leadSourceId = Mastertable::query()
        ->where('alias', Contact::LEAD_SOURCES_MASTERTABLE_ALIAS)
        ->firstOrFail()
        ->items()
        ->orderByDesc('id')
        ->value('id');

    $contact = Contact::factory()->customer()->forWorkspace($this->user->current_workspace_id)->create([
        'lead_source_id' => null,
    ]);

    $response = $this->actingAs($this->user)->put("/contacts/{$contact->id}", [
        'name' => $contact->name,
        'contact_type' => 'customer',
        'lead_source_id' => $leadSourceId,
        'status' => $contact->status,
        'gender' => 'female',
    ]);

    $response->assertRedirect("/contacts/{$contact->id}");

    expect($contact->fresh()?->lead_source_id)->toBe($leadSourceId);
});

test('show page includes the contact lead source', function (): void {
    $permission = PermissionModel::findOrCreate(Permission::ContactsView->value, 'web');

    $this->user->givePermissionTo($permission);

    $leadSource = Mastertable::query()
        ->where('alias', Contact::LEAD_SOURCES_MASTERTABLE_ALIAS)
        ->firstOrFail()
        ->items()
        ->orderBy('id')
        ->firstOrFail();

    $contact = Contact::factory()->create([
        'lead_source_id' => $leadSource->id,
    ]);

    $response = $this->actingAs($this->user)->get("/contacts/{$contact->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('contact.lead_source.id', $leadSource->id)
        ->where('contact.lead_source.name', $leadSource->name));
});

test('index page can filter contacts by lead source', function (): void {
    $permission = PermissionModel::findOrCreate(Permission::ContactsView->value, 'web');

    $this->user->givePermissionTo($permission);

    $leadSources = Mastertable::query()
        ->where('alias', Contact::LEAD_SOURCES_MASTERTABLE_ALIAS)
        ->firstOrFail()
        ->items()
        ->orderBy('id')
        ->get();

    $matchingContact = Contact::factory()->create([
        'name' => 'Cliente filtrado',
        'lead_source_id' => $leadSources[0]->id,
    ]);

    Contact::factory()->create([
        'name' => 'Cliente omitido',
        'lead_source_id' => $leadSources[1]->id,
    ]);

    $response = $this->actingAs($this->user)->get('/contacts?lead_source_id='.$leadSources[0]->id);

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('contacts.appliedFilters.lead_source_id', (string) $leadSources[0]->id)
        ->has('contacts.data', 1)
        ->where('contacts.data.0.id', $matchingContact->id)
        ->where('contacts.data.0.lead_source_id', $leadSources[0]->id));
});

test('edit page sends the birth date in a format the date input can display', function (): void {
    $this->user->givePermissionTo(PermissionModel::findOrCreate(Permission::ContactsEdit->value, 'web'));

    $contact = Contact::factory()->create(['birth_date' => '1990-05-10']);

    $this->actingAs($this->user)
        ->get("/contacts/{$contact->id}/edit")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('contact.birth_date', '1990-05-10'));
});

test('saving the edit form unchanged keeps the birth date', function (): void {
    $this->user->givePermissionTo(PermissionModel::findOrCreate(Permission::ContactsEdit->value, 'web'));

    $contact = Contact::factory()->create(['birth_date' => '1990-05-10'])->fresh();

    $birthDateSentByEditForm = $contact->toArray()['birth_date'];

    $this->actingAs($this->user)
        ->put("/contacts/{$contact->id}", [
            'contact_type' => $contact->contact_type->value,
            'name' => $contact->name,
            'status' => $contact->status,
            'birth_date' => $birthDateSentByEditForm,
            'identification_type' => $contact->identification_type,
            'identification_number' => $contact->identification_number,
            'credit_limit' => '0',
        ])
        ->assertSessionHasNoErrors();

    expect($contact->fresh()->birth_date?->format('Y-m-d'))->toBe('1990-05-10');
});
