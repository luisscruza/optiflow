<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Tax;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Reader;

final class ImportInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:invoices {file : The CSV file path to import} {--limit=50 : Number of invoices to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import invoices and invoice items from CSV file';

    /**
     * Document subtype mapping by prefix.
     *
     * @var array<string, int>
     */
    private array $documentSubtypeMapping = [
        'B01' => 1,  // Factura de Crédito Fiscal
        'B02' => 2,  // Factura de Consumo
        'COT' => 12, // Cotización
        'FCT' => 13, // Facturas de Tenares
        'FCS' => 14, // Factura de Salcedo
        'BC0' => 15, // Factura BC Optical
        'OP0' => 16, // Operativo
        'FLP' => 17, // Factura Laboratorio Optico
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $limit = (int) $this->option('limit');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        $this->info("Starting invoice import from: {$filePath}");
        $this->info("Limit: {$limit} invoices");

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);

            $records = iterator_to_array($csv->getRecords());
            $this->info('Found '.count($records).' CSV rows to process');

            // Don't clean all records upfront - we'll clean as we process them

            // Group records by document_number
            $groupedRecords = $this->groupRecordsByDocumentNumber($records);
            $this->info('Grouped into '.count($groupedRecords).' invoices');

            // Limit the number of invoices to import
            $groupedRecords = array_slice($groupedRecords, 0, $limit, true);
            $this->info("Processing {$limit} invoices...");

            $progressBar = $this->output->createProgressBar(count($groupedRecords));
            $progressBar->start();

            $imported = 0;
            $skipped = 0;
            $errors = [];

            $internalBankAccount = BankAccount::where('is_system_account', true)->first();

            DB::transaction(function () use ($groupedRecords, $progressBar, &$imported, &$skipped, &$errors, $internalBankAccount): void {
                foreach ($groupedRecords as $documentNumber => $invoiceRows) {
                    try {
                        $result = $this->importInvoice($documentNumber, $invoiceRows, $internalBankAccount);
                        if ($result) {
                            $imported++;
                        } else {
                            $skipped++;
                        }
                    } catch (Exception $e) {
                        $skipped++;
                        $errors[] = "Document {$documentNumber}: ".$e->getMessage();
                    }

                    $progressBar->advance();
                }
            });

            $progressBar->finish();
            $this->newLine();

            $this->info('Import completed!');
            $this->info("✓ Imported: {$imported}");
            $this->info("⚠ Skipped: {$skipped}");

            if ($errors !== []) {
                $this->newLine();
                $this->warn('Errors encountered:');
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->error($error);
                }

                if (count($errors) > 10) {
                    $this->warn('... and '.(count($errors) - 10).' more errors');
                }
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Group CSV records by document_number.
     *
     * @param  array<array<string, string>>  $records
     * @return array<string, array<array<string, string>>>
     */
    private function groupRecordsByDocumentNumber(array $records): array
    {
        $grouped = [];

        foreach ($records as $record) {
            $documentNumber = $record['DOCUMENT_NUMBER'] ?? '';
            if (! empty($documentNumber)) {
                $grouped[$documentNumber][] = $record;
            }
        }

        return $grouped;
    }

    /**
     * Import a single invoice with its items.
     *
     * @param  array<array<string, string>>  $invoiceRows
     */
    private function importInvoice(string $documentNumber, array $invoiceRows, BankAccount $internalBankAccount): bool
    {
        if ($invoiceRows === []) {
            return false;
        }

        // Use first row to get invoice header data
        $firstRow = $invoiceRows[0];

        // Check if invoice already exists
        if (Invoice::where('document_number', $documentNumber)->exists()) {
            return false; // Skip duplicates
        }

        // Find contact by name
        $contactName = $this->cleanUtf8String(trim($firstRow['CLIENTE'] ?? ''));
        $contact = Contact::where('name', $contactName)->first();

        if (! $contact) {
            // Try partial match
            $contact = Contact::where('name', 'like', '%'.$contactName.'%')->first();
        }

        if (! $contact) {
            // Create the contact if it doesn't exist
            $contact = Contact::create([
                'name' => $contactName,
                'contact_type' => \App\Enums\ContactType::Customer,
                'identification_type' => \App\Enums\IdentificationType::Cedula, // Default to Cédula
                'identification_number' => null, // We don't have this info from invoice CSV
                'email' => null,
                'phone_primary' => null,
                'status' => 'active',
                'credit_limit' => 0.00,
            ]);

            $this->line("  ℹ Created missing contact: {$contactName}");
        }

        // Get document subtype
        $documentSubtypeId = $this->getDocumentSubtypeId($documentNumber);
        if ($documentSubtypeId === null || $documentSubtypeId === 0) {
            throw new Exception("Could not determine document subtype for: {$documentNumber}");
        }

        // Parse dates
        $issueDate = $this->parseDate($firstRow['FECHA'] ?? '');
        $dueDate = $this->parseDate($firstRow['VENCIMIENTO'] ?? '');

        $status = $this->mapStatus($firstRow['ESTADO'] ?? '');

        // Create invoice
        $invoice = Invoice::create([
            'workspace_id' => (int) ($firstRow['WORKSPACE'] ?? 1),
            'contact_id' => $contact->id,
            'document_subtype_id' => $documentSubtypeId,
            'status' => $status,
            'document_number' => $this->cleanUtf8String($documentNumber),
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'total_amount' => (float) ($firstRow['TOTAL FACTURA'] ?? 0),
            'notes' => $this->cleanUtf8String($firstRow['NOTAS'] ?? ''),
            'currency_id' => 1,
        ]);

        if ($status === 'paid') {
            $this->registerPayment($invoice, $internalBankAccount);
        }

        // Create invoice items
        $itemsCreated = 0;
        foreach ($invoiceRows as $row) {
            if ($this->createInvoiceItem($invoice, $row)) {
                $itemsCreated++;
            }
        }

        // Calculate and update invoice totals from items
        $this->calculateInvoiceTotals($invoice);

        $this->line("  Created invoice {$documentNumber} with {$itemsCreated} items");

        return true;
    }

    /**
     * Create an invoice item from CSV row.
     *
     * @param  array<string, string>  $row
     */
    private function createInvoiceItem(Invoice $invoice, array $row): bool
    {
        $productName = $this->cleanUtf8String(trim($row['PRODUCTO/SERVICIO - NOMBRE'] ?? ''));
        if ($productName === '' || $productName === '0') {
            return false; // Skip rows without product name
        }

        // Try to find product by reference or name
        $productReference = $this->cleanUtf8String(trim($row['PRODUCTO/SERVICIO - REFERENCIA'] ?? ''));
        $product = null;

        // First try to find by SKU/reference if provided
        if ($productReference !== '' && $productReference !== '0') {
            $product = Product::where('sku', $productReference)->first();
        }

        // Then try to find by exact name match
        if (! $product && ($productName !== '' && $productName !== '0')) {
            $product = Product::where('name', $productName)->first();
        }

        // Finally try partial name match
        if (! $product && ($productName !== '' && $productName !== '0')) {
            $product = Product::where('name', 'like', '%'.$productName.'%')->first();
        }

        // Create product if it doesn't exist
        if (! $product) {
            // Create a unique SKU to avoid duplicates
            $baseSku = $this->generateSku($productReference, $productName);
            $sku = $baseSku;
            $counter = 1;

            // Ensure SKU is unique
            while (Product::where('sku', $sku)->exists()) {
                $sku = $baseSku.'-'.$counter;
                $counter++;
            }

            $product = Product::create([
                'name' => $productName,
                'sku' => $sku,
                'description' => null,
                'price' => (float) ($row['PRECIO UNITARIO'] ?? 0),
                'cost' => 0.00,
                'track_stock' => false,
            ]);

            $this->line("    ℹ Created missing product: {$productName} (SKU: {$sku})");
        }

        // Get or create tax based on the CSV data
        $taxName = $this->cleanUtf8String(trim($row['NOMBRE IMPUESTO'] ?? 'ITBIS'));
        $taxRate = (float) ($row['TAX RATE'] ?? 18);

        $tax = Tax::firstOrCreate(
            ['name' => $taxName],
            ['rate' => $taxRate]
        );

        $quantity = (float) ($row['CANTIDAD'] ?? 1);
        $unitPrice = (float) ($row['PRECIO UNITARIO'] ?? 0);
        $discount = (float) ($row['DESCUENTO'] ?? 0);
        $total = (float) ($row['TOTAL SERVICIO'] ?? 0);

        // Calculate tax amount based on the total and tax rate
        $subtotal = $unitPrice * $quantity;
        $discountAmount = $subtotal * ($discount / 100);
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        $taxAmount = $subtotalAfterDiscount * ($taxRate / 100);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'description' => $productName,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_rate' => $discount,
            'tax_id' => $tax->id,
            'tax_rate' => $tax->rate,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);

        return true;
    }

    /**
     * Get document subtype ID from document number.
     */
    private function getDocumentSubtypeId(string $documentNumber): ?int
    {
        foreach ($this->documentSubtypeMapping as $prefix => $subtypeId) {
            if (str_starts_with($documentNumber, $prefix)) {
                return $subtypeId;
            }
        }

        // Try to find by first 3 characters
        $prefix = mb_substr($documentNumber, 0, 3);

        return $this->documentSubtypeMapping[$prefix] ?? null;
    }

    /**
     * Map CSV status to invoice status.
     */
    private function mapStatus(string $status): string
    {
        return match (trim($status)) {
            'Cobrada' => 'paid',
            'Por cobrar' => 'pending_payment',
            'Anulada' => 'cancelled',
            default => 'draft',
        };
    }

    /**
     * Parse date from various formats.
     */
    private function parseDate(string $dateString): ?Carbon
    {
        if ($dateString === '' || $dateString === '0') {
            return null;
        }

        $dateString = trim($dateString);

        // Try common formats with 4-digit years first
        $formats = ['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y', 'Y/m/d'];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($this->isValidDate($date)) {
                    return $date;
                }
            } catch (Exception) {
                // Continue to next format
                continue;
            }
        }

        // Handle 2-digit year formats specially
        $twoDigitFormats = ['d/m/y', 'm/d/y', 'd-m-y'];

        foreach ($twoDigitFormats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                // Adjust for proper century - assume years 00-30 are 2000-2030, 31-99 are 1931-1999
                $year = $date->year;
                if ($year < 100) {
                    if ($year <= 30) {
                        $date->addYears(2000);
                    } elseif ($year <= 99) {
                        $date->addYears(1900);
                    }
                }
                if ($this->isValidDate($date)) {
                    return $date;
                }
            } catch (Exception) {
                // Continue to next format
                continue;
            }
        }

        // Fallback to Carbon's auto-parsing
        try {
            $date = Carbon::parse($dateString);
            if ($this->isValidDate($date)) {
                return $date;
            }
        } catch (Exception) {
            // Auto-parsing failed
        }

        return null;
    }

    /**
     * Validate if a parsed date makes sense for invoice data.
     */
    private function isValidDate(Carbon $date): bool
    {
        $currentYear = now()->year;
        $year = $date->year;

        // Accept dates from 1990 to 10 years in the future
        return $year >= 1990 && $year <= ($currentYear + 10);
    }

    /**
     * Calculate and update invoice totals from its items.
     */
    private function calculateInvoiceTotals(Invoice $invoice): void
    {
        $items = $invoice->items()->get();

        $subtotalAmount = 0;
        $discountAmount = 0;
        $taxAmount = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $itemSubtotal = $item->quantity * $item->unit_price;
            $itemDiscountAmount = $itemSubtotal * ($item->discount_rate / 100);
            $itemTaxAmount = $item->tax_amount; // This is already calculated

            $subtotalAmount += $itemSubtotal;
            $discountAmount += $itemDiscountAmount;
            $taxAmount += $itemTaxAmount;
            $totalAmount += $item->total;
        }

        // Update the invoice with calculated totals
        $invoice->update([
            'subtotal_amount' => $subtotalAmount,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Clean UTF-8 characters from a CSV record.
     *
     * @param  array<string, string>  $record
     * @return array<string, string>
     */
    private function cleanUtf8Record(array $record): array
    {
        $cleanRecord = [];

        foreach ($record as $key => $value) {
            $cleanRecord[$this->cleanUtf8String($key)] = $this->cleanUtf8String($value);
        }

        return $cleanRecord;
    }

    /**
     * Clean UTF-8 characters from a string.
     */
    private function cleanUtf8String(string $str): string
    {
        // Remove null bytes and other control characters
        $str = str_replace(["\0", "\x0B"], '', $str);

        // Convert to UTF-8 if it's not already
        if (! mb_check_encoding($str, 'UTF-8')) {
            // Try to detect encoding and convert
            $encoding = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $str = mb_convert_encoding($str, 'UTF-8', $encoding);
            } else {
                // If we can't detect encoding, remove invalid UTF-8 sequences
                $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            }
        }

        // Remove any remaining invalid UTF-8 characters
        $str = filter_var($str, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        // Trim whitespace
        return trim($str ?? '');
    }

    /**
     * Generate a clean SKU from product reference and name.
     */
    private function generateSku(string $productReference, string $productName): string
    {
        if ($productReference !== '' && $productReference !== '0') {
            // Handle scientific notation or large numbers
            if (preg_match('/^\d+\.?\d*[eE][+-]?\d+$/', $productReference)) {
                // Convert scientific notation back to regular number
                $productReference = number_format((float) $productReference, 0, '', '');
            }

            // Clean the reference to make it a valid SKU
            $sku = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $productReference);
            $sku = trim((string) $sku, '-');

            if ($sku !== '' && $sku !== '0' && mb_strlen($sku) <= 50) { // Reasonable SKU length limit
                return mb_strtoupper($sku);
            }
        }

        // Fallback to product name slug
        $slug = Str::slug($productName);
        if (mb_strlen($slug) > 50) {
            $slug = mb_substr($slug, 0, 47).'...';
        }

        return mb_strtoupper($slug);
    }

    /**
     * Register a payment for the invoice.
     */
    private function registerPayment(Invoice $invoice, BankAccount $internalBankAccount): void
    {
        $invoice->payments()->create([
            'bank_account_id' => $internalBankAccount->id,
            'currency_id' => 1,
            'amount' => $invoice->total_amount,
            'payment_date' => now(),
            'payment_method' => 'other',
            'note' => 'Payment registered during invoice import',
        ]);

    }
}
