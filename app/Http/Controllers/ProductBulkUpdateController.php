<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PrepareProductBulkUpdateAction;
use App\Actions\ProcessProductBulkUpdateAction;
use App\Enums\Permission;
use App\Http\Requests\UploadProductBulkUpdateRequest;
use App\Models\ProductBulkUpdate;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ProductBulkUpdateController
{
    public function index(#[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ProductsEdit) && $user->can(Permission::InventoryAdjust), 403);

        return Inertia::render('product-bulk-updates/index', [
            'latestBulkUpdate' => ProductBulkUpdate::query()->orderByDesc('id')->first(),
            'bulkUpdates' => ProductBulkUpdate::query()
                ->select([
                    'id',
                    'original_filename',
                    'status',
                    'summary',
                    'processed_rows',
                    'successful_rows',
                    'error_rows',
                    'created_at',
                    'updated_at',
                ])
                ->orderByDesc('id')
                ->paginate(10),
        ]);
    }

    public function store(
        UploadProductBulkUpdateRequest $request,
        #[CurrentUser] User $user,
        PrepareProductBulkUpdateAction $prepareProductBulkUpdateAction,
    ): RedirectResponse {
        abort_unless($user->can(Permission::ProductsEdit) && $user->can(Permission::InventoryAdjust), 403);

        $bulkUpdate = $prepareProductBulkUpdateAction->handle($request->file('file'), $user);

        $message = $bulkUpdate->error_rows > 0
            ? "Se preparo la vista previa con {$bulkUpdate->error_rows} fila(s) con errores. Corrigelas antes de confirmar."
            : 'Vista previa generada. Revisa los cambios y confirma para aplicarlos.';

        return redirect()
            ->route('product-bulk-updates.index')
            ->with($bulkUpdate->error_rows > 0 ? 'warning' : 'success', $message);
    }

    public function confirm(
        ProductBulkUpdate $productBulkUpdate,
        #[CurrentUser] User $user,
        ProcessProductBulkUpdateAction $processProductBulkUpdateAction,
    ): RedirectResponse {
        abort_unless($user->can(Permission::ProductsEdit) && $user->can(Permission::InventoryAdjust), 403);

        $bulkUpdate = $processProductBulkUpdateAction->handle($productBulkUpdate, $user);

        $message = $bulkUpdate->error_rows > 0
            ? "Actualizacion masiva completada con {$bulkUpdate->error_rows} fila(s) con errores."
            : 'Actualizacion masiva completada correctamente.';

        return redirect()
            ->route('product-bulk-updates.index')
            ->with($bulkUpdate->error_rows > 0 ? 'warning' : 'success', $message);
    }
}
