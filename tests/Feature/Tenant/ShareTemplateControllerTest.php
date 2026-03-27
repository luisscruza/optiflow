<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Permission as PermissionModel;
use App\Models\ShareTemplate;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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

test('index lists share templates', function (): void {
    $permission = PermissionModel::findOrCreate(Permission::ConfigurationView->value, 'web');

    $this->user->givePermissionTo($permission);

    $response = $this->actingAs($this->user)->get('/share-templates');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('configuration/share-templates-index')
        ->has('templates', 6));
});

test('edit updates a share template', function (): void {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $permission = PermissionModel::findOrCreate(Permission::ConfigurationEdit->value, 'web');

    $this->user->givePermissionTo($permission);

    $template = ShareTemplate::query()->firstOrFail();

    $response = $this->actingAs($this->user)->put("/share-templates/{$template->id}", [
        'entity_type' => $template->entity_type->value,
        'channel' => $template->channel->value,
        'name' => 'Plantilla actualizada',
        'subject' => 'Asunto actualizado',
        'body' => 'Hola {{contact.name}}, revisa aqui {{shareable_link}}',
        'is_active' => true,
    ]);

    $response->assertRedirect('/share-templates');

    expect($template->fresh())
        ->name->toBe('Plantilla actualizada')
        ->subject->toBe('Asunto actualizado');
});
