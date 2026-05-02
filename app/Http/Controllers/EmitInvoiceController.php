<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\Permission;
use App\Jobs\SubmitInvoiceToEasyFactuJob;
use App\Models\Invoice;
use App\Models\User;
use App\Services\EasyFactuService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

/**
 * Submit an electronic invoice draft to DGII via EasyFactu.
 */
final class EmitInvoiceController
{
    public function __invoke(Invoice $invoice, #[CurrentUser] User $user, EasyFactuService $easyFactu): RedirectResponse
    {
        abort_unless($user->can(Permission::InvoicesEdit), 403);

        if (! $invoice->canBeEmitted()) {
            return redirect()->back()->with('error', 'Esta factura no puede ser emitida. Verifica que sea electrónica, esté en borrador, y tenga un ID de EasyFactu.');
        }

        if (! $easyFactu->isConfigured()) {
            return redirect()->back()->with('error', 'EasyFactu no está configurado para la facturación electrónica.');
        }

        $invoice->update([
            'status' => InvoiceStatus::Submitted,
            'dgii_status' => 'Pendiente de envío a DGII',
        ]);

        SubmitInvoiceToEasyFactuJob::dispatch($invoice->id);

        return redirect()->back()->with('success', 'Factura en proceso de envío a la DGII. Reintentaremos automáticamente si el servicio tarda en responder.');
    }
}
