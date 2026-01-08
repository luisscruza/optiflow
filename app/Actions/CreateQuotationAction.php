<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\QuotationResult;
use App\Enums\QuotationStatus;
use App\Models\DocumentSubtype;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Workspace;
use App\Support\NCFValidator;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateQuotationAction
{
    /**
     * @throws Throwable
     */
    public function handle(Workspace $workspace, array $data): QuotationResult
    {
        return DB::transaction(function () use ($workspace, $data): QuotationResult {

            $documentSubtype = DocumentSubtype::query()->findOrFail($data['document_subtype_id']);

            if (! NCFValidator::validate($data['ncf'], $documentSubtype, $data)) {
                return new QuotationResult(error: 'El NCF proporcionado no es válido.');
            }

            $quotation = $this->createDocument($workspace, $data);

            $this->updateNumerator($documentSubtype, $data['ncf']);

            $items = array_filter($data['items'], fn (array $item): bool => isset($item['product_id'], $item['quantity'], $item['unit_price']) &&
                $item['quantity'] > 0);

            $this->createQuotationItems($quotation, $items);

            return new QuotationResult(
                quotation: $quotation->load(['contact', 'documentSubtype', 'items.product'])
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createDocument(Workspace $workspace, array $data): Quotation
    {
        return Quotation::query()->create([
            'workspace_id' => $workspace->id,
            'contact_id' => $data['contact_id'],
            'document_subtype_id' => $data['document_subtype_id'],
            'status' => QuotationStatus::NonConverted,
            'document_number' => $data['ncf'],
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'currency_id' => 1, // TODO: Allow to switch currency...
            'total_amount' => $data['total'],
            'subtotal_amount' => $data['subtotal'],
            'discount_amount' => $data['discount_total'],
            'tax_amount' => $data['tax_amount'],
        ]);
    }

    /**
     * Creates quotation items without stock movements.
     *
     * @param array<int, array{
     *     product_id: int,
     *     description?: string,
     *     quantity: float,
     *     unit_price: float,
     *     discount_rate?: float,
     *     discount_amount?: float,
     *     tax_rate?: float,
     *     tax_amount?: float,
     *     total?: float,
     *     taxes?: array<int, array{id: int, rate: float, amount: float}>,
     * }> $items
     */
    private function createQuotationItems(Quotation $quotation, array $items): void
    {
        foreach ($items as $item) {
            $product = Product::query()->findOrFail($item['product_id']);

            /** @var \App\Models\QuotationItem $quotationItem */
            $quotationItem = $quotation->items()->create([
                'product_id' => $product->id,
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['quantity'] * $item['unit_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'discount_rate' => $item['discount_rate'] ?? 0,
                'tax_rate' => $item['tax_rate'] ?? 0,
                'tax_amount' => $item['tax_amount'] ?? 0,
                'tax_id' => 1, // @TODO: Remove legacy tax_id column after full migration
                'total' => $item['total'],
            ]);

            // Sync multi-tax relationship if taxes array is provided
            if (! empty($item['taxes'])) {
                $taxesData = [];
                foreach ($item['taxes'] as $tax) {
                    $taxesData[$tax['id']] = [
                        'rate' => $tax['rate'],
                        'amount' => $tax['amount'],
                    ];
                }
                $quotationItem->taxes()->sync($taxesData);
            }
        }
    }

    /**
     *  Updates the next number of the document type.
     */
    private function updateNumerator(DocumentSubtype $documentSubtype, string $ncf): void
    {
        $number = (int) mb_ltrim(mb_substr($ncf, mb_strlen($documentSubtype->prefix)), '0');

        if ($number >= $documentSubtype->next_number) {
            $documentSubtype->update(['next_number' => $number + 1]);
        }
    }
}
