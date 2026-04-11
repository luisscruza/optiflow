<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\CompanyDetail;
use App\Models\StockMovement;
use App\Models\User;
use App\Tables\StockTransfersTable;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

final class StreamStockTransferPdfController extends Controller
{
    public function __invoke(StockMovement $stockMovement, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::InventoryView), 403);

        $workspace = Context::get('workspace');

        abort_if($workspace === null, 404);

        $movementType = $stockMovement->type instanceof BackedEnum
            ? $stockMovement->type->value
            : (string) $stockMovement->type;

        abort_unless(in_array($movementType, StockTransfersTable::transferTypes(), true), 404);

        abort_unless(
            $stockMovement->from_workspace_id === $workspace->id || $stockMovement->to_workspace_id === $workspace->id,
            404,
        );

        $stockMovement->load(['product', 'fromWorkspace', 'toWorkspace', 'createdBy']);

        $referenceNumber = $stockMovement->reference_number ?: "TR-{$stockMovement->id}";

        $pdf = Pdf::loadView('stock-transfers.pdf', [
            'transfer' => $stockMovement,
            'company' => CompanyDetail::getAll(),
            'workspace' => $workspace,
            'referenceNumber' => $referenceNumber,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("transferencia-{$referenceNumber}.pdf");
    }
}
