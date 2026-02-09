<?php

declare(strict_types=1);

use App\Actions\StockAdjustmentAction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Models\Workspace;

test('adjusts stock in the selected workspace', function (): void {
    $currentWorkspace = Workspace::factory()->create();
    $targetWorkspace = Workspace::factory()->create();

    $user = User::factory()->create([
        'current_workspace_id' => $currentWorkspace->id,
    ]);

    $user->workspaces()->attach($currentWorkspace->id);
    $user->workspaces()->attach($targetWorkspace->id);

    $product = Product::factory()->tracksStock()->create();

    ProductStock::factory()
        ->forProductAndWorkspace($product, $targetWorkspace)
        ->withQuantity(5)
        ->create();

    $movement = app(StockAdjustmentAction::class)->handle($user, [
        'product_id' => $product->id,
        'workspace_id' => $targetWorkspace->id,
        'adjustment_type' => 'set_quantity',
        'quantity' => 12,
        'reason' => 'Ajuste rapido',
    ]);

    $updatedStock = ProductStock::query()
        ->where('product_id', $product->id)
        ->where('workspace_id', $targetWorkspace->id)
        ->firstOrFail();

    expect((float) $updatedStock->quantity)->toBe(12.0)
        ->and($movement->workspace_id)->toBe($targetWorkspace->id)
        ->and((float) $movement->quantity)->toBe(7.0);
});

test('rejects stock adjustment for an inaccessible workspace', function (): void {
    $currentWorkspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();

    $user = User::factory()->create([
        'current_workspace_id' => $currentWorkspace->id,
    ]);

    $user->workspaces()->attach($currentWorkspace->id);

    $product = Product::factory()->tracksStock()->create();

    $action = app(StockAdjustmentAction::class);

    expect(fn () => $action->handle($user, [
        'product_id' => $product->id,
        'workspace_id' => $otherWorkspace->id,
        'adjustment_type' => 'set_quantity',
        'quantity' => 3,
        'reason' => 'Ajuste no permitido',
    ]))
        ->toThrow(InvalidArgumentException::class, 'User does not have access to the selected workspace.');
});
