<?php

declare(strict_types=1);

use App\Enums\ContactType;
use App\Enums\Permission;
use App\Models\CompanyDetail;
use App\Models\Contact;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use App\Models\ProductRecipe;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\PermissionFactory;

beforeEach(function (): void {
    $this->workspace = Workspace::factory()->create([
        'name' => 'Sucursal Centro',
    ]);

    $this->user = User::factory()->create([
        'current_workspace_id' => $this->workspace->id,
    ]);
});

function grantPermission(User $user, Permission $permission): void
{
    $grantedPermission = PermissionFactory::new()->create([
        'name' => $permission->value,
        'guard_name' => 'web',
    ]);

    $user->givePermissionTo($grantedPermission);
}

function createProductRecipeMastertable(): Mastertable
{
    return Mastertable::query()->firstOrCreate(
        ['alias' => ProductRecipe::PRODUCTS_MASTERTABLE_ALIAS],
        [
            'name' => 'Productos recetarios',
            'description' => 'Listado configurable de productos para recetarios.',
        ],
    );
}

test('index page loads successfully', function (): void {
    grantPermission($this->user, Permission::PrescriptionsView);

    $response = $this->actingAs($this->user)
        ->get('/product-recipes');

    $response->assertSuccessful();
    $response->assertSee('Recetario de productos');
});

test('it stores a product recipe', function (): void {
    grantPermission($this->user, Permission::PrescriptionsCreate);
    grantPermission($this->user, Permission::PrescriptionsView);

    $mastertable = createProductRecipeMastertable();
    $product = MastertableItem::factory()->create([
        'mastertable_id' => $mastertable->id,
        'name' => 'Refresh Optive',
    ]);

    $contact = Contact::factory()->create();
    $optometrist = Contact::factory()->create([
        'contact_type' => ContactType::Optometrist,
        'name' => 'Arnold Brito',
    ]);

    $response = $this->actingAs($this->user)
        ->post('/product-recipes', [
            'contact_id' => $contact->id,
            'optometrist_id' => $optometrist->id,
            'product_id' => $product->id,
            'indication' => 'Aplicar dos veces al dia por 20 dias.',
        ]);

    $response->assertRedirect('/product-recipes');

    $this->assertDatabaseHas('product_recipes', [
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
        'contact_id' => $contact->id,
        'optometrist_id' => $optometrist->id,
        'product_id' => $product->id,
        'indication' => 'Aplicar dos veces al dia por 20 dias.',
    ]);
});

test('it streams the product recipe pdf', function (): void {
    grantPermission($this->user, Permission::PrescriptionsView);

    CompanyDetail::setByKey('company_name', 'Centro Optico');

    $mastertable = createProductRecipeMastertable();
    $product = MastertableItem::factory()->create([
        'mastertable_id' => $mastertable->id,
        'name' => 'Refresh Optive',
    ]);

    $contact = Contact::factory()->create([
        'name' => 'Karina Lantigua',
    ]);
    $optometrist = Contact::factory()->create([
        'contact_type' => ContactType::Optometrist,
        'name' => 'Arnold Brito',
    ]);

    $productRecipe = ProductRecipe::factory()->create([
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
        'contact_id' => $contact->id,
        'optometrist_id' => $optometrist->id,
        'product_id' => $product->id,
        'indication' => 'Uso de Refresh Optive dos veces al dia.',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('product-recipes.pdf', $productRecipe));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});
