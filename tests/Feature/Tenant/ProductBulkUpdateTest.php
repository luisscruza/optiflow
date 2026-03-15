<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Product;
use App\Models\ProductBulkUpdate;
use App\Models\ProductStock;
use App\Models\Tax;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\PermissionFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake();

    $this->workspaceA = Workspace::factory()->create([
        'name' => 'Sucursal Centro',
        'slug' => 'sucursal-centro',
        'code' => 'CTR',
    ]);

    $this->workspaceB = Workspace::factory()->create([
        'name' => 'Sucursal Norte',
        'slug' => 'sucursal-norte',
        'code' => 'NTE',
    ]);

    $this->user = User::factory()->create([
        'current_workspace_id' => $this->workspaceA->id,
    ]);

    $this->user->workspaces()->attach([$this->workspaceA->id, $this->workspaceB->id]);
});

function grantBulkUpdatePermissions(User $user): void
{
    foreach ([Permission::ProductsEdit, Permission::InventoryAdjust] as $permission) {
        $grantedPermission = PermissionFactory::new()->create([
            'name' => $permission->value,
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo($grantedPermission);
    }
}

test('template download includes editable and workspace stock columns', function (): void {
    grantBulkUpdatePermissions($this->user);

    $tax = Tax::factory()->create([
        'name' => 'ITBIS 18%',
        'rate' => 18,
    ]);

    $product = Product::factory()->tracksStock()->create([
        'name' => 'Lente Azul',
        'sku' => 'SKU-001',
        'price' => 25.5,
        'default_tax_id' => $tax->id,
        'is_active' => true,
    ]);

    ProductStock::factory()->forProductAndWorkspace($product, $this->workspaceA)->withQuantity(12)->create();
    ProductStock::factory()->forProductAndWorkspace($product, $this->workspaceB)->withQuantity(7)->create();

    $response = $this->actingAs($this->user)->get(route('product-bulk-updates.template'));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain('SKU,NAME,DESCRIPTION,PRODUCT_TYPE,PRICE,COST,TRACK_STOCK,ALLOW_NEGATIVE_STOCK,TAX_1_NAME,TAX_1_RATE,STATUS,WORKSPACE_CTR_STOCK,WORKSPACE_NTE_STOCK')
        ->and($content)->toContain('SKU-001')
        ->and($content)->toContain('Lente Azul');
});

test('bulk update prepares preview and confirms product status and absolute workspace stock', function (): void {
    grantBulkUpdatePermissions($this->user);

    $tax = Tax::factory()->create([
        'name' => 'ITBIS 18%',
        'rate' => 18,
    ]);

    $product = Product::factory()->tracksStock()->create([
        'name' => 'Armazon Clasico',
        'sku' => 'SKU-200',
        'price' => 100,
        'default_tax_id' => $tax->id,
        'is_active' => true,
    ]);

    ProductStock::factory()->forProductAndWorkspace($product, $this->workspaceA)->withQuantity(10)->create();
    ProductStock::factory()->forProductAndWorkspace($product, $this->workspaceB)->withQuantity(4)->create();

    $csv = <<<'CSV'
SKU,NAME,DESCRIPTION,PRODUCT_TYPE,PRICE,COST,TRACK_STOCK,ALLOW_NEGATIVE_STOCK,TAX_1_NAME,TAX_1_RATE,STATUS,WORKSPACE_CTR_STOCK,WORKSPACE_NTE_STOCK
SKU-200,Armazon Clasico,Montura actualizada,product,120.00,50.00,true,false,ITBIS 18%,18.00,inactive,25,3
CSV;

    $file = UploadedFile::fake()->createWithContent('bulk-update.csv', $csv);

    $response = $this->actingAs($this->user)->post('/product-bulk-updates', [
        'file' => $file,
    ]);

    $response->assertRedirect(route('product-bulk-updates.index'));

    $bulkUpdate = ProductBulkUpdate::query()->latest()->first();

    expect($bulkUpdate?->status)->toBe('ready')
        ->and($bulkUpdate?->summary['products_updated'])->toBe(1)
        ->and($bulkUpdate?->preview_rows)->toHaveCount(1);

    expect($product->refresh()->is_active)->toBeTrue()
        ->and($product->getStockQuantityForWorkspace($this->workspaceA))->toBe(10.0);

    $confirmResponse = $this->actingAs($this->user)->post(route('product-bulk-updates.confirm', $bulkUpdate));

    $confirmResponse->assertRedirect(route('product-bulk-updates.index'));

    expect($product->refresh()->is_active)->toBeFalse()
        ->and((float) $product->price)->toBe(120.0)
        ->and($product->description)->toBe('Montura actualizada')
        ->and($product->getStockQuantityForWorkspace($this->workspaceA))->toBe(25.0)
        ->and($product->getStockQuantityForWorkspace($this->workspaceB))->toBe(3.0);

    $this->assertDatabaseHas('product_bulk_updates', [
        'status' => 'completed',
        'total_rows' => 1,
        'processed_rows' => 1,
        'successful_rows' => 1,
        'error_rows' => 0,
    ]);
});

test('bulk update stores row validation errors and does not create missing sku products', function (): void {
    grantBulkUpdatePermissions($this->user);

    $product = Product::factory()->tracksStock()->create([
        'sku' => 'SKU-300',
    ]);

    ProductStock::factory()->forProductAndWorkspace($product, $this->workspaceA)->withQuantity(5)->create();

    $csv = <<<'CSV'
SKU,NAME,DESCRIPTION,PRODUCT_TYPE,PRICE,COST,TRACK_STOCK,ALLOW_NEGATIVE_STOCK,TAX_1_NAME,TAX_1_RATE,STATUS,WORKSPACE_CTR_STOCK
UNKNOWN,Producto sin SKU,Desc,product,80.00,20.00,true,false,,,active,10
SKU-300,Producto con estado invalido,Desc,product,80.00,20.00,true,false,,,archived,10
CSV;

    $file = UploadedFile::fake()->createWithContent('bulk-update-errors.csv', $csv);

    $response = $this->actingAs($this->user)->post('/product-bulk-updates', [
        'file' => $file,
    ]);

    $response->assertRedirect(route('product-bulk-updates.index'));

    $bulkUpdate = ProductBulkUpdate::query()->latest()->first();

    expect($bulkUpdate)->not->toBeNull()
        ->and($bulkUpdate?->status)->toBe('failed')
        ->and($bulkUpdate?->error_rows)->toBe(2)
        ->and($bulkUpdate?->validation_errors)->toHaveCount(2);

    expect(Product::query()->where('sku', 'UNKNOWN')->exists())->toBeFalse();
});

test('bulk update preview shows per product changes before confirm', function (): void {
    grantBulkUpdatePermissions($this->user);

    $product = Product::factory()->tracksStock()->create([
        'sku' => 'SKU-PREVIEW',
        'name' => 'Producto original',
        'price' => 15,
        'is_active' => true,
    ]);

    ProductStock::factory()->forProductAndWorkspace($product, $this->workspaceA)->withQuantity(1)->create();

    $csv = <<<'CSV'
SKU,NAME,DESCRIPTION,PRODUCT_TYPE,PRICE,COST,TRACK_STOCK,ALLOW_NEGATIVE_STOCK,TAX_1_NAME,TAX_1_RATE,STATUS,WORKSPACE_CTR_STOCK
SKU-PREVIEW,Producto actualizado,,product,20.00,,true,false,,,inactive,4
CSV;

    $file = UploadedFile::fake()->createWithContent('bulk-update-preview.csv', $csv);

    $this->actingAs($this->user)->post('/product-bulk-updates', [
        'file' => $file,
    ]);

    $bulkUpdate = ProductBulkUpdate::query()->latest()->first();

    expect($bulkUpdate?->status)->toBe('ready')
        ->and($bulkUpdate?->preview_rows)->toHaveCount(1)
        ->and($bulkUpdate?->preview_rows[0]['changes'])->toMatchArray([
            ['field' => 'NAME', 'from' => 'Producto original', 'to' => 'Producto actualizado'],
        ]);
});

test('bulk update matches sku values formatted in scientific notation', function (): void {
    grantBulkUpdatePermissions($this->user);

    $product = Product::factory()->tracksStock()->create([
        'sku' => '846567000000',
        'name' => 'Producto numerico',
        'price' => 40,
        'is_active' => true,
    ]);

    ProductStock::factory()->forProductAndWorkspace($product, $this->workspaceA)->withQuantity(2)->create();

    $csv = <<<'CSV'
SKU,NAME,DESCRIPTION,PRODUCT_TYPE,PRICE,COST,TRACK_STOCK,ALLOW_NEGATIVE_STOCK,TAX_1_NAME,TAX_1_RATE,STATUS,WORKSPACE_CTR_STOCK
8.46567E+11,Producto numerico actualizado,,product,55.00,,true,false,,,inactive,6
CSV;

    $file = UploadedFile::fake()->createWithContent('bulk-update-scientific.csv', $csv);

    $response = $this->actingAs($this->user)->post('/product-bulk-updates', [
        'file' => $file,
    ]);

    $response->assertRedirect(route('product-bulk-updates.index'));

    $bulkUpdate = ProductBulkUpdate::query()->latest()->first();

    expect($bulkUpdate?->status)->toBe('ready');

    $this->actingAs($this->user)->post(route('product-bulk-updates.confirm', $bulkUpdate));

    expect($product->refresh()->name)->toBe('Producto numerico actualizado')
        ->and($product->is_active)->toBeFalse()
        ->and((float) $product->price)->toBe(55.0)
        ->and($product->getStockQuantityForWorkspace($this->workspaceA))->toBe(6.0);
});
