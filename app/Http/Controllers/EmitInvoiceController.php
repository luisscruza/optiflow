<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\Permission;
use App\Exceptions\EasyFactuException;
use App\Models\Invoice;
use App\Models\User;
use App\Services\EasyFactuService;
use App\Support\EasyFactuInvoiceMetadata;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

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

        try {
            $response = $easyFactu->submitInvoice($invoice->easyfactu_invoice_id);

            $efInvoice = $response['invoice'] ?? [];

            // Determine the new status based on EasyFactu's response
            $dgiiStatus = $efInvoice['dgii_status'] ?? null;
            $efStatus = $efInvoice['status'] ?? 'submitted';

            $newStatus = match (true) {
                $efStatus === 'accepted' => InvoiceStatus::DgiiAccepted,
                $efStatus === 'rejected' => InvoiceStatus::DgiiRejected,
                default => InvoiceStatus::Submitted,
            };

            $invoice->update([
                'status' => $newStatus,
                'dgii_status' => $dgiiStatus,
                'dgii_track_id' => $efInvoice['dgii_track_id'] ?? $invoice->dgii_track_id,
                'dgii_security_code' => $efInvoice['security_code'] ?? $invoice->dgii_security_code,
                'dgii_qr_code_url' => $efInvoice['qr_code_url'] ?? $invoice->dgii_qr_code_url,
                'dgii_signed_at' => EasyFactuInvoiceMetadata::extractSignedAt($efInvoice) ?? $invoice->dgii_signed_at,
                'encf' => $efInvoice['encf'] ?? $invoice->encf,
                'document_number' => $efInvoice['encf'] ?? $invoice->document_number,
            ]);

            if ($newStatus === InvoiceStatus::DgiiAccepted) {
                $invoice->update(['status' => InvoiceStatus::PendingPayment]);
            }

            $message = match ($newStatus) {
                InvoiceStatus::DgiiAccepted => 'Factura aceptada por la DGII.',
                InvoiceStatus::DgiiRejected => 'La factura fue rechazada por la DGII.',
                default => 'Factura enviada a la DGII. Refresca el estado para ver la respuesta.',
            };

            return redirect()->back()->with('success', $message);
        } catch (EasyFactuException $e) {
            Log::error('Failed to emit invoice via EasyFactu', [
                'invoice_id' => $invoice->id,
                'easyfactu_invoice_id' => $invoice->easyfactu_invoice_id,
                'error' => $e->getMessage(),
                'errors' => $e->errors,
            ]);

            return redirect()->back()->with('error', 'Error emitiendo factura: '.$e->getMessage());
        }
    }
}
