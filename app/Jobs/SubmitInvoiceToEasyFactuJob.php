<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Exceptions\EasyFactuException;
use App\Models\Invoice;
use App\Services\EasyFactuService;
use App\Support\EasyFactuInvoiceMetadata;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SubmitInvoiceToEasyFactuJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    public int $timeout = 90;

    public bool $failOnTimeout = true;

    public function __construct(public int $invoiceId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 180];
    }

    public function handle(EasyFactuService $easyFactu): void
    {
        $invoice = Invoice::query()->withoutWorkspaceScope()->find($this->invoiceId);

        if (! $invoice || ! $invoice->isElectronic() || ! $invoice->easyfactu_invoice_id) {
            return;
        }

        try {
            $response = $easyFactu->submitInvoice($invoice->easyfactu_invoice_id);
            $this->applySubmissionResponse($invoice, $response);
        } catch (EasyFactuException $exception) {
            Log::warning('EasyFactu invoice submission attempt failed', [
                'invoice_id' => $invoice->id,
                'easyfactu_invoice_id' => $invoice->easyfactu_invoice_id,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
                'errors' => $exception->errors,
            ]);

            if ($this->shouldRetry($exception)) {
                throw $exception;
            }

            $this->markInvoiceAsSubmissionFailed($invoice, $exception->getMessage());
        }
    }

    public function failed(?Throwable $exception): void
    {
        $invoice = Invoice::query()->withoutWorkspaceScope()->find($this->invoiceId);

        if (! $invoice) {
            return;
        }

        $this->markInvoiceAsSubmissionFailed($invoice, $exception?->getMessage() ?? 'Error desconocido enviando factura a EasyFactu.');

        Log::error('Failed to emit invoice via EasyFactu', [
            'invoice_id' => $invoice->id,
            'easyfactu_invoice_id' => $invoice->easyfactu_invoice_id,
            'error' => $exception?->getMessage(),
            'errors' => $exception instanceof EasyFactuException ? $exception->errors : [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function applySubmissionResponse(Invoice $invoice, array $response): void
    {
        $efInvoice = $response['invoice'] ?? [];
        $efStatus = $efInvoice['status'] ?? 'submitted';

        $newStatus = match (true) {
            $efStatus === 'accepted' => InvoiceStatus::DgiiAccepted,
            $efStatus === 'rejected' => InvoiceStatus::DgiiRejected,
            $efStatus === 'error' => InvoiceStatus::Draft,
            default => InvoiceStatus::Submitted,
        };

        $invoice->update([
            'status' => $newStatus,
            'dgii_status' => $efInvoice['dgii_status'] ?? $invoice->dgii_status,
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
    }

    private function shouldRetry(EasyFactuException $exception): bool
    {
        if ($exception->getPrevious() instanceof ConnectionException) {
            return true;
        }

        return $exception->getCode() >= 500;
    }

    private function markInvoiceAsSubmissionFailed(Invoice $invoice, string $message): void
    {
        $invoice->update([
            'status' => InvoiceStatus::Draft,
            'dgii_status' => 'Error sincronización: '.$message,
        ]);
    }
}
