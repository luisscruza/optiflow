<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductSearch
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, ?Workspace $workspace = null, int $limit = 25): array
    {
        $search = mb_trim($query);

        if (mb_strlen($search) < 2) {
            return [];
        }

        return $this->baseQuery($workspace)
            ->where(function (Builder $builder) use ($search): void {
                $like = "%{$search}%";

                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Product $product): array => $this->toArray($product, $workspace))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int|null>  $productIds
     * @return array<int, array<string, mixed>>
     */
    public function findByIds(array $productIds, ?Workspace $workspace = null): array
    {
        $ids = collect($productIds)
            ->filter(fn (mixed $id): bool => is_int($id) && $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return $this->baseQuery($workspace)
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => $this->toArray($product, $workspace))
            ->values()
            ->all();
    }

    /**
     * @return Builder<Product>
     */
    private function baseQuery(?Workspace $workspace = null): Builder
    {
        return Product::query()
            ->with(['defaultTax'])
            ->when($workspace, function (Builder $builder) use ($workspace): void {
                $builder->with(['stocks' => function (HasMany $stockBuilder) use ($workspace): void {
                    $stockBuilder->where('workspace_id', $workspace->id);
                }]);
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Product $product, ?Workspace $workspace = null): array
    {
        $stock = $workspace ? $product->stocks->first() : null;

        if ($stock instanceof ProductStock) {
            $stockQuantity = (float) $stock->quantity;
            $minimumQuantity = (float) $stock->minimum_quantity;
        } else {
            $stockQuantity = 0;
            $minimumQuantity = 0;
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->description,
            'product_type' => $product->product_type->value,
            'price' => (float) $product->price,
            'track_stock' => (bool) $product->track_stock,
            'default_tax_id' => $product->default_tax_id,
            'default_tax' => $product->defaultTax,
            'stock_quantity' => $stockQuantity,
            'minimum_quantity' => $minimumQuantity,
            'stock_status' => $this->getStockStatus($product, $stock),
            'created_at' => optional($product->created_at)?->toISOString(),
            'updated_at' => optional($product->updated_at)?->toISOString(),
        ];
    }

    private function getStockStatus(Product $product, ?ProductStock $stock): string
    {
        if (! $product->track_stock) {
            return 'not_tracked';
        }

        if (! $stock instanceof ProductStock || $stock->quantity <= 0) {
            return 'out_of_stock';
        }

        if ($stock->quantity <= $stock->minimum_quantity) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
