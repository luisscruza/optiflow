<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Contact;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\PermissionFactory;

beforeEach(function (): void {
    $workspace = Workspace::factory()->create();
    $this->user = User::factory()->create(['current_workspace_id' => $workspace->id]);
});

test('edit page loads without error when contacts have appended attributes', function (): void {
    $permission = PermissionFactory::new()->create([
        'name' => Permission::ContactsEdit->value,
        'guard_name' => 'web',
    ]);

    $this->user->givePermissionTo($permission);

    $contact = Contact::factory()->create();

    // Create additional active contacts that will appear in the relationship picker
    Contact::factory()->count(3)->create(['status' => 'active']);

    $response = $this->actingAs($this->user)
        ->get("/contacts/{$contact->id}/edit");

    $response->assertSuccessful();
});

test('create page loads without error when contacts have appended attributes', function (): void {
    $permission = PermissionFactory::new()->create([
        'name' => Permission::ContactsCreate->value,
        'guard_name' => 'web',
    ]);

    $this->user->givePermissionTo($permission);

    // Create active contacts that will appear in the relationship picker
    Contact::factory()->count(3)->create(['status' => 'active']);

    $response = $this->actingAs($this->user)
        ->get('/contacts/create');

    $response->assertSuccessful();
});
