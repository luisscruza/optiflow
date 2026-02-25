<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactType;
use App\Enums\DocumentType;
use App\Enums\IdentificationType;
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
use Illuminate\Support\Str;
use League\Csv\Reader;

final class ProcessInvoiceImportAction
{
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
     * @var null|callable(string): void
     */
    private $onMessage = null;

    /**
     * @param  null|callable(int $total): void  $onStart
     * @param  null|callable(int $processed, int $total, int $imported, int $skipped, int $errors): void  $onProgress
     * @param  null|callable(string $message): void  $onMessage
     * @return array<string, mixed>
     */
    public function handle(
        string $filePath,
        int $limit,
        int $offset,
        ?callable $onStart = null,
        ?callable $onProgress = null,
        ?callable $onMessage = null
    ): array {
        $this->onMessage = $onMessage;

        if (! file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $this->message("Empiezando la importación de facturas desde el archivo: {$filePath}");
        $this->message("Límite: {$limit} facturas");
        $this->message("Saltando: {$offset} facturas");

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $records = iterator_to_array($csv->getRecords());
        $this->message('Encontradas totales en el archivo CSV: '.count($records));

        $groupedRecords = $this->groupRecordsByDocumentNumber($records);
        $this->message('Total de facturas únicas a procesar: '.count($groupedRecords));

        $groupedRecords = array_slice($groupedRecords, $offset, $limit, true);
        $total = count($groupedRecords);
        $this->message('Procesando un total de facturas: '.$total);

        if ($onStart) {
            $onStart($total);
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $processed = 0;

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

        foreach ($groupedRecords as $documentNumber => $invoiceRows) {
            try {
                $result = $this->importInvoice($documentNumber, $invoiceRows, $internalBankAccount);
                if ($result) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (Exception $exception) {
                $skipped++;
                $errors[] = "Document {$documentNumber}: ".$exception->getMessage();
            }

            $processed++;

            if ($onProgress) {
                $onProgress($processed, $total, $imported, $skipped, count($errors));
            }
        }

        $this->updateDocumentSubtypeNextNumbers();

        $this->message('Importación completada.');
        $this->message("✓ Importadas: {$imported}");
        $this->message("⚠ Omitidas: {$skipped}");

        return [
            'total' => $total,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    private function message(string $message): void
    {
        if ($this->onMessage) {
            ($this->onMessage)($message);
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

        $firstRow = $invoiceRows[0];

        $contactName = $this->cleanUtf8String(mb_trim($firstRow['CLIENTE'] ?? ''));
        $contact = Contact::query()->where('name', $contactName)->first();

        if (! $contact) {
            $contact = Contact::query()->where('name', 'like', '%'.$contactName.'%')->first();
        }

        if (! $contact) {
            $contact = Contact::query()->create([
                'name' => $contactName,
                'contact_type' => ContactType::Customer,
                'identification_type' => IdentificationType::Cedula,
                'identification_number' => null,
                'email' => null,
                'phone_primary' => null,
                'status' => 'active',
                'credit_limit' => 0.00,
            ]);

            $this->message("  ℹ Creado contacto faltante: {$contactName}");
        }

        $documentSubtypeId = $this->getDocumentSubtypeId($documentNumber);
        if ($documentSubtypeId === null || $documentSubtypeId === 0) {
            throw new Exception("Could not determine document subtype for: {$documentNumber}");
        }

        $issueDate = $this->parseDate($firstRow['FECHA'] ?? '');
        $dueDate = $this->parseDate($firstRow['VENCIMIENTO'] ?? '');

        $status = $this->mapStatus($firstRow['ESTADO'] ?? '');

        $workspaceId = $this->getWorkspaceIdFromRow($firstRow);

        if (Invoice::query()->withoutWorkspaceScope()->where('document_number', $documentNumber)->exists()) {
            $this->message("  ℹ Se saltó la factura {$documentNumber} porque ya existe");

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

        $itemsCreated = 0;
        foreach ($invoiceRows as $row) {
            if ($this->createInvoiceItem($invoice, $row)) {
                $itemsCreated++;
            }
        }

        $this->calculateInvoiceTotals($invoice);

        $this->message("  Creada factura {$documentNumber} con {$itemsCreated} items");

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
            return false;
        }

        $productReference = $this->cleanUtf8String(mb_trim($row['PRODUCTO/SERVICIO - REFERENCIA'] ?? ''));
        $product = null;

        if ($productReference !== '' && $productReference !== '0') {
            $product = Product::query()->where('sku', $productReference)->first();
        }

        if (! $product && ($productName !== '' && $productName !== '0')) {
            $product = Product::query()->where('name', $productName)->first();
        }

        if (! $product && ($productName !== '' && $productName !== '0')) {
            $product = Product::query()->where('name', 'like', '%'.$productName.'%')->first();
        }

        if (! $product) {
            $baseSku = $this->generateSku($productReference, $productName);
            $sku = $baseSku;
            $counter = 1;

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

            $this->message("    ℹ Creado producto faltante: {$productName} (SKU: {$sku})");
        }

        $quantity = (float) ($row['CANTIDAD'] ?? 1);
        $unitPrice = (float) ($row['PRECIO UNITARIO'] ?? 0);
        $discount = (float) ($row['DESCUENTO'] ?? 0);
        $total = (float) ($row['PRODUCTO/SERVICIO - TOTAL'] ?? $row['TOTAL SERVICIO'] ?? 0);

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
                'type' => DocumentType::Invoice,
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

        $formats = ['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y', 'Y/m/d'];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($this->isValidDate($date)) {
                    return $date;
                }
            } catch (Exception) {
                continue;
            }
        }

        $twoDigitFormats = ['d/m/y', 'm/d/y', 'd-m-y'];

        foreach ($twoDigitFormats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
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
                continue;
            }
        }

        try {
            $date = Carbon::parse($dateString);
            if ($this->isValidDate($date)) {
                return $date;
            }
        } catch (Exception) {
            // ignore
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
            $itemTaxAmount = $item->tax_amount;

            $subtotalAmount += $itemSubtotal;
            $discountAmount += $itemDiscountAmount;
            $taxAmount += $itemTaxAmount;
            $totalAmount += $item->total;
        }

        $invoice->update([
            'subtotal_amount' => $subtotalAmount,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Clean UTF-8 characters from a string.
     */
    private function cleanUtf8String(string $str): string
    {
        $str = str_replace(["\0", "\x0B"], '', $str);

        if (! mb_check_encoding($str, 'UTF-8')) {
            $encoding = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $str = mb_convert_encoding($str, 'UTF-8', $encoding);
            } else {
                $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            }
        }

        $str = filter_var($str, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        return mb_trim($str);
    }

    /**
     * Generate a clean SKU from product reference and name.
     */
    private function generateSku(string $productReference, string $productName): string
    {
        if ($productReference !== '' && $productReference !== '0') {
            if (preg_match('/^\d+\.?\d*[eE][+-]?\d+$/', $productReference)) {
                $productReference = number_format((float) $productReference, 0, '', '');
            }

            $sku = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $productReference);
            $sku = mb_trim((string) $sku, '-');

            if ($sku !== '' && $sku !== '0' && mb_strlen($sku) <= 50) {
                return mb_strtoupper($sku);
            }
        }

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
