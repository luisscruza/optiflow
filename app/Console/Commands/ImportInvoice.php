<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\CreateWorkspaceAction;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;

final class ImportInvoice extends Command
{
    use HasATenantsOption, TenantAwareCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:invoices {file : The CSV file path to import} {--limit=50000000 : Number of invoices to import} {--offset=0 : Number of invoices to skip before importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import invoices and invoice items from CSV file';

    /**
     * Document subtype names by prefix.
     *
     * @var array<string, string>
     */
    private array $documentSubtypeMapping = [
        'B01' => 'Factura de Crédito Fiscal',
        'B02' => 'Factura de Consumo',
        'COT' => 'Cotización',
        'FCT' => 'Facturas de Tenares',
        'FCS' => 'Factura de Salcedo',
        'BC0' => 'Factura BC Optical',
        'OP0' => 'Operativo',
        'FLP' => 'Factura Laboratorio Optico',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        $this->info("Empiezando la importación de facturas desde el archivo: {$filePath}");
        $this->info("Límite: {$limit} facturas");
        $this->info("Saltando: {$offset} facturas");

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);

            $records = iterator_to_array($csv->getRecords());
            $this->info('Encontradas totales en el archivo CSV: '.count($records));

            // Don't clean all records upfront - we'll clean as we process them

            // Group records by document_number
            $groupedRecords = $this->groupRecordsByDocumentNumber($records);
            $this->info('Total de facturas únicas a procesar: '.count($groupedRecords));

            // Limit the number of invoices to import
            $groupedRecords = array_slice($groupedRecords, $offset, $limit, true);

            $this->info('Procesando un total de facturas: '.count($groupedRecords));

            $progressBar = $this->output->createProgressBar(count($groupedRecords));
            $progressBar->start();

            $imported = 0;
            $skipped = 0;
            $errors = [];

            $internalBankAccount = BankAccount::query()
                ->firstOrCreate(
                    ['is_system_account' => true],
                    [
                        'name' => 'Cuenta Interna',
                        'description' => 'Cuenta bancaria interna para registros del sistema',
                        'currency_id' => Currency::first()->id,
                        'type' => 'cash',
                        'account_number' => 0,
                        'initial_balance' => 0.00,
                        'initial_balance_date' => now(),
                        'is_active' => false,
                    ]
                );

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
            $this->updateDocumentSubtypeNextNumbers();
            $progressBar->finish();
            $this->newLine();

            $this->info('Importación completada.');
            $this->info("✓ Importadas: {$imported}");
            $this->info("⚠ Omitidas: {$skipped}");

            if ($errors !== []) {
                $this->newLine();
                $this->warn('Errores encontrados:');
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->error($error);
                }

                if (count($errors) > 10) {
                    $this->warn('... y '.(count($errors) - 10).' errores más');
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
            $documentNumber = $this->cleanUtf8String(mb_trim($record['DOCUMENT_NUMBER'] ?? ''));
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
        $documentNumber = $this->cleanUtf8String(mb_trim($documentNumber));
        if ($documentNumber === '') {
            return false;
        }

        if ($invoiceRows === []) {
            return false;
        }

        // Use first row to get invoice header data
        $firstRow = $invoiceRows[0];

        // Find contact by name
        $contactName = $this->cleanUtf8String(mb_trim($firstRow['CLIENTE'] ?? ''));
        $contact = Contact::query()->where('name', $contactName)->first();

        if (! $contact) {
            // Try partial match
            $contact = Contact::query()->where('name', 'like', '%'.$contactName.'%')->first();
        }

        if (! $contact) {
            // Create the contact if it doesn't exist
            $contact = Contact::query()->create([
                'name' => $contactName,
                'contact_type' => \App\Enums\ContactType::Customer,
                'identification_type' => \App\Enums\IdentificationType::Cedula, // Default to Cédula
                'identification_number' => null, // We don't have this info from invoice CSV
                'email' => null,
                'phone_primary' => null,
                'status' => 'active',
                'credit_limit' => 0.00,
            ]);

            $this->line("  ℹ Creado contacto faltante: {$contactName}");
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

        $workspaceId = $this->getWorkspaceIdFromRow($firstRow);

        if (Invoice::query()->withoutWorkspaceScope()->where('document_number', $documentNumber)->exists()) {
            $this->line("  ℹ Se saltó la factura {$documentNumber} porque ya existe");

            return false;
        }

        /** @var Invoice $invoice */
        $invoice = Invoice::query()->create([
            'workspace_id' => $workspaceId,
            'contact_id' => $contact->id,
            'document_subtype_id' => $documentSubtypeId,
            'status' => $status,
            'document_number' => $this->cleanUtf8String($documentNumber),
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'total_amount' => (float) ($firstRow['TOTAL - FACTURA'] ?? $firstRow['TOTAL FACTURA'] ?? 0),
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

        $this->line("  Creada factura {$documentNumber} con {$itemsCreated} items");

        return true;
    }

    /**
     * Create an invoice item from CSV row.
     *
     * @param  array<string, string>  $row
     */
    private function createInvoiceItem(Invoice $invoice, array $row): bool
    {
        $productName = $this->cleanUtf8String(mb_trim($row['PRODUCTO/SERVICIO - NOMBRE'] ?? ''));
        if ($productName === '' || $productName === '0') {
            return false; // Skip rows without product name
        }

        // Try to find product by reference or name
        $productReference = $this->cleanUtf8String(mb_trim($row['PRODUCTO/SERVICIO - REFERENCIA'] ?? ''));
        $product = null;

        // First try to find by SKU/reference if provided
        if ($productReference !== '' && $productReference !== '0') {
            $product = Product::query()->where('sku', $productReference)->first();
        }

        // Then try to find by exact name match
        if (! $product && ($productName !== '' && $productName !== '0')) {
            $product = Product::query()->where('name', $productName)->first();
        }

        // Finally try partial name match
        if (! $product && ($productName !== '' && $productName !== '0')) {
            $product = Product::query()->where('name', 'like', '%'.$productName.'%')->first();
        }

        // Create product if it doesn't exist
        if (! $product) {
            // Create a unique SKU to avoid duplicates
            $baseSku = $this->generateSku($productReference, $productName);
            $sku = $baseSku;
            $counter = 1;

            // Ensure SKU is unique
            while (Product::query()->where('sku', $sku)->exists()) {
                $sku = $baseSku.'-'.$counter;
                $counter++;
            }

            $product = Product::query()->create([
                'name' => $productName,
                'sku' => $sku,
                'description' => null,
                'price' => (float) ($row['PRECIO UNITARIO'] ?? 0),
                'cost' => 0.00,
                'track_stock' => false,
            ]);

            $this->line("    ℹ Creado producto faltante: {$productName} (SKU: {$sku})");
        }

        $quantity = (float) ($row['CANTIDAD'] ?? 1);
        $unitPrice = (float) ($row['PRECIO UNITARIO'] ?? 0);
        $discount = (float) ($row['DESCUENTO'] ?? 0);
        $total = (float) ($row['PRODUCTO/SERVICIO - TOTAL'] ?? $row['TOTAL SERVICIO'] ?? 0);

        // Calculate tax amount based on the total and tax rate
        $subtotal = $unitPrice * $quantity;
        $discountAmount = $subtotal * ($discount / 100);
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        $taxes = $this->extractTaxes($row, $subtotalAfterDiscount);
        $taxAmount = array_reduce($taxes, fn (float $sum, array $tax): float => $sum + $tax['amount'], 0.0);
        $taxRate = array_reduce($taxes, fn (float $sum, array $tax): float => $sum + $tax['rate'], 0.0);
        $legacyTaxId = $taxes !== []
            ? $taxes[0]['id']
            : $this->resolveLegacyTaxId();

        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'description' => $productName,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'discount_rate' => $discount,
            'tax_id' => $legacyTaxId,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);

        if ($taxes !== []) {
            $taxesData = [];
            foreach ($taxes as $tax) {
                $taxesData[$tax['id']] = [
                    'rate' => $tax['rate'],
                    'amount' => $tax['amount'],
                ];
            }

            $invoiceItem->taxes()->sync($taxesData);
        }

        return true;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<int, array{id: int, rate: float, amount: float}>
     */
    private function extractTaxes(array $row, float $subtotalAfterDiscount): array
    {
        $taxes = [];

        for ($index = 1; $index <= 3; $index++) {
            $nameKey = "IMPUESTO_{$index}_NOMBRE";
            $rateKey = "IMPUESTO_{$index}_PORCENTAJE";
            $amountKey = "IMPUESTO_{$index}_VALOR";

            $taxName = $this->cleanUtf8String(mb_trim($row[$nameKey] ?? ''));
            $taxRate = (float) ($row[$rateKey] ?? 0);
            $taxAmount = (float) ($row[$amountKey] ?? 0);

            if ($taxName === '' && $taxRate === 0.0 && $taxAmount === 0.0) {
                continue;
            }

            if ($taxName === '') {
                $taxName = 'ITBIS';
            }

            if ($taxAmount === 0.0 && $taxRate > 0 && $subtotalAfterDiscount > 0) {
                $taxAmount = $subtotalAfterDiscount * ($taxRate / 100);
            }

            if ($taxRate === 0.0 && $taxAmount > 0 && $subtotalAfterDiscount > 0) {
                $taxRate = ($taxAmount / $subtotalAfterDiscount) * 100;
            }

            $tax = Tax::query()->firstOrCreate(['name' => $taxName], ['rate' => $taxRate]);

            $taxes[] = [
                'id' => $tax->id,
                'rate' => $taxRate,
                'amount' => $taxAmount,
            ];
        }

        return $taxes;
    }

    private function resolveLegacyTaxId(): int
    {
        $taxId = Tax::query()->default()->value('id') ?? Tax::query()->value('id');

        if ($taxId) {
            return (int) $taxId;
        }

        return Tax::query()->create([
            'name' => 'ITBIS',
            'rate' => 0,
            'is_default' => true,
        ])->id;
    }

    /**
     * Get document subtype ID from document number.
     */
    private function getDocumentSubtypeId(string $documentNumber): ?int
    {
        $prefix = mb_strtoupper(mb_substr($documentNumber, 0, 3));
        if ($prefix === '') {
            return null;
        }

        $name = $this->documentSubtypeMapping[$prefix] ?? "Documento {$prefix}";

        $subtype = DocumentSubtype::query()->firstOrCreate(
            ['prefix' => $prefix],
            [
                'name' => $name,
                'type' => \App\Enums\DocumentType::Invoice,
                'is_default' => false,
                'valid_until_date' => null,
                'start_number' => 1,
                'end_number' => null,
                'next_number' => 1,
            ]
        );

        return $subtype->id;
    }

    /**
     * Map CSV status to invoice status.
     */
    private function mapStatus(string $status): string
    {
        return match (mb_trim($status)) {
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

        $dateString = mb_trim($dateString);

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
        return mb_trim($str);
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
            $sku = mb_trim((string) $sku, '-');

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
            'note' => 'Pago importado desde Alegra',
        ]);
    }

    /**
     * Get workspace ID from CSV row.
     */
    private function getWorkspaceIdFromRow(array $row): int
    {
        $workspaceCode = $this->cleanUtf8String(mb_trim($row['WORKSPACE_ID'] ?? ''));
        $workspaceName = $this->cleanUtf8String(mb_trim($row['WORKSPACE_NAME'] ?? ''));

        if ($workspaceCode !== '') {
            $workspace = Workspace::query()
                ->where('code', $workspaceCode)
                ->first();

            if ($workspace) {
                return $workspace->id;
            }
        }

        if ($workspaceName !== '') {
            $workspace = Workspace::query()
                ->where('name', $workspaceName)
                ->first();

            if ($workspace) {
                return $workspace->id;
            }
        }

        $name = $workspaceName !== '' ? $workspaceName : ($workspaceCode !== '' ? $workspaceCode : 'Workspace');
        $code = $workspaceCode !== '' ? $workspaceCode : null;

        $workspace = app(CreateWorkspaceAction::class)->handle(
            User::query()->where('business_role', 'admin')->first(),
            [
                'name' => $name,
                'code' => $code ?? Str::lower(Str::slug($name)),
                'description' => $name,
            ]
        );

        return $workspace->id;
    }

    /**
     * Update next numbers for document subtypes.
     */
    private function updateDocumentSubtypeNextNumbers(): void
    {
        $subtypes = DocumentSubtype::query()->forInvoice()->get();

        foreach ($subtypes as $subtype) {
            $maxDocumentNumber = Invoice::query()
                ->where('document_subtype_id', $subtype->id)
                ->max('document_number');

            if ($maxDocumentNumber) {
                // Extract numeric part from document number
                $numberPart = preg_replace('/\D/', '', $maxDocumentNumber);
                if (is_numeric($numberPart)) {
                    $nextNumber = (int) $numberPart + 1;
                    if ($nextNumber > $subtype->next_number) {
                        $subtype->update(['next_number' => $nextNumber]);
                    }
                }
            }
        }
    }
}
