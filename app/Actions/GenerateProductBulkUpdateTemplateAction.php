<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\ProductBulkUpdate;
use App\Models\User;
use App\Models\Workspace;
use App\Scopes\ActiveProductScope;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GenerateProductBulkUpdateTemplateAction
{
    public function handle(User $user): StreamedResponse
    {
        $workspaces = $this->availableWorkspaces($user);
        $workspaceIds = $workspaces->pluck('id')->all();

        $products = Product::query()
            ->withoutGlobalScope(ActiveProductScope::class)
            ->with([
                'defaultTax:id,name,rate',
                'stocks' => fn ($query) => $query
                    ->withoutWorkspaceScope()
                    ->whereIn('workspace_id', $workspaceIds)
                    ->select(['id', 'product_id', 'workspace_id', 'quantity']),
            ])
            ->orderBy('name')
            ->get();

        $headers = [
            'PRODUCT_ID',
            ...ProductBulkUpdate::editableColumns(),
            ...$workspaces->map(fn (Workspace $workspace): string => ProductBulkUpdate::workspaceStockColumnName($workspace))->all(),
        ];

        return response()->streamDownload(function () use ($headers, $products, $workspaces): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fputcsv($output, $headers);

            foreach ($products as $product) {
                $stockByWorkspace = $product->stocks->keyBy('workspace_id');

                $row = [
                    $product->id,
                    $product->sku,
                    $product->name,
                    $product->description,
                    $product->product_type->value,
                    number_format((float) $product->price, 2, '.', ''),
                    $product->cost !== null ? number_format((float) $product->cost, 2, '.', '') : '',
                    $product->track_stock ? 'true' : 'false',
                    $product->allow_negative_stock ? 'true' : 'false',
                    $product->defaultTax?->name,
                    $product->defaultTax ? number_format((float) $product->defaultTax->rate, 2, '.', '') : '',
                    $product->is_active ? 'active' : 'inactive',
                ];

                foreach ($workspaces as $workspace) {
                    $row[] = $product->track_stock
                        ? number_format((float) ($stockByWorkspace->get($workspace->id)?->quantity ?? 0), 2, '.', '')
                        : '';
                }

                fputcsv($output, $row);
            }

            fclose($output);
        }, 'product-bulk-update-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return Collection<int, Workspace>
     */
    private function availableWorkspaces(User $user): Collection
    {
        $query = Workspace::query()->select(['id', 'name', 'slug', 'code'])->orderBy('name');

        if (! $user->can(Permission::ViewAllLocations)) {
            $query->whereIn('id', $user->workspaces()->select('workspaces.id'));
        }

        return $query->get();
    }
}
