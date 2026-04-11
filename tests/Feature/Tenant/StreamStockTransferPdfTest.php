<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\StockMovementType;
use App\Models\CompanyDetail;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\PermissionFactory;

beforeEach(function (): void {
    $this->workspace = Workspace::factory()->create([
        'name' => 'Sucursal Central',
    ]);

    $this->user = User::factory()->create([
        'current_workspace_id' => $this->workspace->id,
    ]);

    $this->user->workspaces()->attach($this->workspace->id, [
        'role' => 'admin',
        'joined_at' => now(),
    ]);
});

function grantInventoryViewPermission(User $user): void
{
    $grantedPermission = PermissionFactory::new()->create([
        'name' => Permission::InventoryView->value,
        'guard_name' => 'web',
    ]);

    $user->givePermissionTo($grantedPermission);
}

test('it streams the stock transfer pdf', function (): void {
    grantInventoryViewPermission($this->user);

    CompanyDetail::setByKey('company_name', 'Optica Test');

    $destinationWorkspace = Workspace::factory()->create([
        'name' => 'Sucursal Norte',
    ]);

    $product = Product::factory()->create([
        'name' => 'Lente progresivo',
        'sku' => 'LEN-001',
        'unit' => 'Unidad',
    ]);

    $transfer = StockMovement::factory()->create([
        'workspace_id' => $this->workspace->id,
        'product_id' => $product->id,
        'type' => StockMovementType::TRANSFER_OUT,
        'quantity' => 3,
        'from_workspace_id' => $this->workspace->id,
        'to_workspace_id' => $destinationWorkspace->id,
        'reference_number' => 'TR-1001',
        'user_id' => $this->user->id,
        'note' => 'Mercancia lista para recepcion',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('stock-transfers.pdf', $transfer));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});

test('it forbids users without inventory view permission from streaming the stock transfer pdf', function (): void {
    $destinationWorkspace = Workspace::factory()->create();

    $transfer = StockMovement::factory()->create([
        'workspace_id' => $this->workspace->id,
        'type' => StockMovementType::TRANSFER_OUT,
        'from_workspace_id' => $this->workspace->id,
        'to_workspace_id' => $destinationWorkspace->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('stock-transfers.pdf', $transfer));

    $response->assertForbidden();
});

test('it returns not found when the movement is not a transfer visible to the current workspace', function (): void {
    grantInventoryViewPermission($this->user);

    $otherWorkspace = Workspace::factory()->create();

    $movement = StockMovement::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'type' => StockMovementType::SALE,
        'from_workspace_id' => $otherWorkspace->id,
        'to_workspace_id' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('stock-transfers.pdf', $movement));

    $response->assertNotFound();
});
