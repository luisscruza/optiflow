<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\User;
use App\Models\Workspace;
use App\Tables\ConsolidatedStockTable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ConsolidatedStockController
{
    public function __invoke(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::InventoryView), 403);

        $workspacesQuery = Workspace::query()->select(['id', 'name'])->orderBy('name');

        if (! $user->can(Permission::ViewAllLocations)) {
            $workspacesQuery->whereIn('id', $user->workspaces()->select('workspaces.id'));
        }

        return Inertia::render('inventory/consolidated-stock/index', [
            'stock' => ConsolidatedStockTable::make($request)->forWorkspaces($workspacesQuery->get()),
        ]);
    }
}
