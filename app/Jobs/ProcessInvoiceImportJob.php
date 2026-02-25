<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ProcessInvoiceImportAction;
use App\Models\InvoiceImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProcessInvoiceImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public function __construct(public int $invoiceImportId) {}

    public function handle(ProcessInvoiceImportAction $action): void
    {
        $invoiceImport = InvoiceImport::query()->find($this->invoiceImportId);

        if (! $invoiceImport) {
            return;
        }

        $invoiceImport->markAsProcessing();

        if (! Storage::exists($invoiceImport->file_path)) {
            $invoiceImport->markAsFailed('El archivo seleccionado no existe.');

            return;
        }

        $absolutePath = Storage::path($invoiceImport->file_path);

        $lastUpdated = 0;
        $updateEvery = 10;

        try {
            $result = $action->handle(
                filePath: $absolutePath,
                limit: $invoiceImport->limit,
                offset: $invoiceImport->offset,
                onStart: function (int $total) use ($invoiceImport): void {
                    $invoiceImport->updateProgress(0, 0, 0, 0, $total);
                },
                onProgress: function (int $processed, int $total, int $imported, int $skipped, int $errors) use ($invoiceImport, &$lastUpdated, $updateEvery): void {
                    if ($processed === $total || ($processed - $lastUpdated) >= $updateEvery) {
                        $invoiceImport->updateProgress($processed, $imported, $skipped, $errors, $total);
                        $lastUpdated = $processed;
                    }
                }
            );

            $invoiceImport->markAsCompleted(0, $this->formatSummaryOutput($result));
        } catch (Throwable $exception) {
            $invoiceImport->markAsFailed($exception->getMessage());
        }
    }

    public function failed(?Throwable $exception): void
    {
        $invoiceImport = InvoiceImport::query()->find($this->invoiceImportId);

        if (! $invoiceImport || $invoiceImport->isCompleted()) {
            return;
        }

        $invoiceImport->markAsFailed($exception?->getMessage());
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function formatSummaryOutput(array $result): string
    {
        $imported = (int) ($result['imported'] ?? 0);
        $skipped = (int) ($result['skipped'] ?? 0);
        $total = (int) ($result['total'] ?? 0);

        return implode("\n", [
            'Importación completada.',
            "Total: {$total}",
            "Importadas: {$imported}",
            "Omitidas: {$skipped}",
        ]);
    }
}
