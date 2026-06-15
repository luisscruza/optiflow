<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Contact;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\PermissionFactory;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $workspace = Workspace::factory()->create();
    $this->user = User::factory()->create(['current_workspace_id' => $workspace->id]);
});

function grantContactPermissions(User $user, Permission ...$permissions): void
{
    foreach ($permissions as $permission) {
        $permissionModel = PermissionFactory::new()->create([
            'name' => $permission->value,
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo($permissionModel);
    }
}

test('it uploads documents to a contact', function (): void {
    grantContactPermissions($this->user, Permission::ContactsEdit, Permission::ContactsView);

    $contact = Contact::factory()->forWorkspace($this->user->current_workspace_id)->create();

    $response = $this->actingAs($this->user)->post("/contacts/{$contact->id}/documents", [
        'documents' => [
            UploadedFile::fake()->create('cedula.pdf', 512, 'application/pdf'),
            UploadedFile::fake()->image('perfil.png')->size(256),
        ],
    ]);

    $response->assertRedirect("/contacts/{$contact->id}");

    expect($contact->fresh()?->getMedia('documents'))->toHaveCount(2);
});

test('it rejects contact documents larger than 3mb', function (): void {
    grantContactPermissions($this->user, Permission::ContactsEdit);

    $contact = Contact::factory()->forWorkspace($this->user->current_workspace_id)->create();

    $response = $this->actingAs($this->user)->post("/contacts/{$contact->id}/documents", [
        'documents' => [
            UploadedFile::fake()->create('pesado.pdf', 3073, 'application/pdf'),
        ],
    ]);

    $response->assertSessionHasErrors('documents.0');

    expect($contact->fresh()?->getMedia('documents'))->toHaveCount(0);
});

test('it forbids uploading contact documents without permission', function (): void {
    $contact = Contact::factory()->forWorkspace($this->user->current_workspace_id)->create();

    $response = $this->actingAs($this->user)->post("/contacts/{$contact->id}/documents", [
        'documents' => [
            UploadedFile::fake()->create('cedula.pdf', 512, 'application/pdf'),
        ],
    ]);

    $response->assertForbidden();
});

test('it deletes a contact document', function (): void {
    grantContactPermissions($this->user, Permission::ContactsEdit);

    $contact = Contact::factory()->forWorkspace($this->user->current_workspace_id)->create();
    $media = $contact->addMedia(UploadedFile::fake()->create('contrato.pdf', 512, 'application/pdf'))
        ->toMediaCollection('documents');

    $response = $this->actingAs($this->user)->delete("/contacts/{$contact->id}/documents/{$media->id}");

    $response->assertRedirect("/contacts/{$contact->id}");

    expect($contact->fresh()?->getMedia('documents'))->toHaveCount(0);
});

test('show page includes serialized contact documents', function (): void {
    grantContactPermissions($this->user, Permission::ContactsView);

    $contact = Contact::factory()->forWorkspace($this->user->current_workspace_id)->create();
    $media = $contact->addMedia(UploadedFile::fake()->create('historial.pdf', 256, 'application/pdf'))
        ->toMediaCollection('documents');

    $response = $this->actingAs($this->user)->get("/contacts/{$contact->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('documents', 1)
        ->where('documents.0.id', $media->id)
        ->where('documents.0.file_name', 'historial.pdf')
        ->where('documents.0.mime_type', 'application/pdf'));
});
