<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final readonly class CreateProductAction
{
    public function __construct(
        private SetInitialStockAction $setInitialStockAction
    ) {}

    /**
     * Create a new product.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Product
    {
        return DB::transaction(function () use ($user, $data) {
            // Create the product
            $product = Product::query()->create([
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'] ?? null,
                'product_type' => $data['product_type'] ?? ProductType::Product->value,
                'price' => $data['price'],
                'cost' => $data['cost'] ?? null,
                'track_stock' => $data['track_stock']
                    ?? (($data['product_type'] ?? ProductType::Product->value) === ProductType::Product->value),
                'allow_negative_stock' => $data['allow_negative_stock'] ?? false,
                'default_tax_id' => $data['default_tax_id'] ?? null,
            ]);

            $workspaceInitialQuantities = is_array($data['workspace_initial_quantities'] ?? null)
                ? $data['workspace_initial_quantities']
                : [];

            if ($product->track_stock && $workspaceInitialQuantities !== []) {
                $workspaceIds = array_map('intval', array_keys($workspaceInitialQuantities));
                $workspaceIds = array_values(array_filter($workspaceIds, fn (int $id): bool => $id > 0));

                if ($workspaceIds !== []) {
                    $allowedWorkspaceIds = $user->can(Permission::ViewAllLocations)
                        ? Workspace::query()->whereIn('id', $workspaceIds)->pluck('id')->all()
                        : $user->workspaces()->whereIn('workspaces.id', $workspaceIds)->pluck('workspaces.id')->all();

                    $workspaces = Workspace::query()->whereIn('id', $allowedWorkspaceIds)->get()->keyBy('id');

                    foreach ($workspaceInitialQuantities as $workspaceId => $quantityValue) {
                        if ($quantityValue === null || $quantityValue === '') {
                            continue;
                        }

                        $workspace = $workspaces->get((int) $workspaceId);
                        if (! $workspace instanceof Workspace) {
                            continue;
                        }

                        $stockData = [
                            'product_id' => $product->id,
                            'quantity' => (float) $quantityValue,
                            'minimum_quantity' => $data['minimum_quantity'] ?? null,
                            'unit_cost' => $data['unit_cost'] ?? null,
                            'notes' => 'Initial stock setup during product creation',
                        ];

                        $this->setInitialStockAction->handle($user, $stockData, $workspace);
                    }
                }
            } elseif ($product->track_stock && $user->current_workspace_id && isset($data['initial_quantity'])) {
                $stockData = [
                    'product_id' => $product->id,
                    'quantity' => $data['initial_quantity'] ?? 0,
                    'minimum_quantity' => $data['minimum_quantity'] ?? null,
                    'unit_cost' => $data['unit_cost'] ?? null,
                    'notes' => 'Initial stock setup during product creation',
                ];

                $this->setInitialStockAction->handle($user, $stockData);
            }

            return $product->load(['defaultTax']);
        });
    }
}
