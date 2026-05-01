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
 * Refresh the DGII status for an electronic invoice via EasyFactu.
 */
final class RefreshInvoiceStatusController
{
    public function __invoke(Invoice $invoice, #[CurrentUser] User $user, EasyFactuService $easyFactu): RedirectResponse
    {
        abort_unless($user->can(Permission::InvoicesView), 403);

        if (! $invoice->isElectronic() || ! $invoice->easyfactu_invoice_id) {
            return redirect()->back()->with('error', 'Esta factura no es electrónica o no está vinculada a EasyFactu.');
        }

        try {
            $response = $easyFactu->getInvoiceStatus($invoice->easyfactu_invoice_id);

            $efInvoice = $response['invoice'] ?? [];
            $efStatus = $efInvoice['status'] ?? null;

            $updateData = [
                'dgii_status' => $efInvoice['dgii_status'] ?? $invoice->dgii_status,
                'dgii_track_id' => $efInvoice['dgii_track_id'] ?? $invoice->dgii_track_id,
                'dgii_security_code' => $efInvoice['security_code'] ?? $invoice->dgii_security_code,
                'dgii_qr_code_url' => $efInvoice['qr_code_url'] ?? $invoice->dgii_qr_code_url,
                'dgii_signed_at' => EasyFactuInvoiceMetadata::extractSignedAt($efInvoice) ?? $invoice->dgii_signed_at,
            ];

            // Transition status based on EasyFactu response
            if ($efStatus === 'accepted' && $invoice->status === InvoiceStatus::Submitted) {
                $updateData['status'] = InvoiceStatus::PendingPayment;

                $invoice->update($updateData);

                return redirect()->back()->with('success', 'La factura fue aceptada por la DGII.');
            }

            if ($efStatus === 'rejected' && $invoice->status === InvoiceStatus::Submitted) {
                $updateData['status'] = InvoiceStatus::DgiiRejected;

                $invoice->update($updateData);

                return redirect()->back()->with('error', 'La factura fue rechazada por la DGII.');
            }

            $invoice->update($updateData);

            return redirect()->back()->with('success', 'Estado actualizado. Estado actual: '.($efInvoice['dgii_status'] ?? 'Pendiente'));
        } catch (EasyFactuException $e) {
            Log::error('Failed to refresh invoice status from EasyFactu', [
                'invoice_id' => $invoice->id,
                'easyfactu_invoice_id' => $invoice->easyfactu_invoice_id,
                'error' => $e->getMessage(),
                'errors' => $e->errors,
            ]);

            return redirect()->back()->with('error', 'Error consultando estado: '.$e->getMessage());
        }
    }
}
