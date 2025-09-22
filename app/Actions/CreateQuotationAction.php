<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\QuotationResult;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\DocumentSubtype;
use App\Models\Product;
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
        return DB::transaction(function () use ($workspace, $data) {

            $documentSubtype = DocumentSubtype::findOrFail($data['document_subtype_id']);

            if (! NCFValidator::validate($data['ncf'], $documentSubtype, $data)) {
                return new QuotationResult(error: 'El NCF proporcionado no es válido.');
            }

            $document = $this->createDocument($workspace, $data);

            $this->updateNumerator($documentSubtype, $data['ncf']);

            $items = array_filter($data['items'], function ($item) {
                return isset($item['product_id'], $item['quantity'], $item['unit_price']) &&
                    $item['quantity'] > 0;
            });

            $this->createQuotationItems($document, $items);

            return new QuotationResult(
                quotation: $document->load(['contact', 'documentSubtype', 'items.product']));
        });
    }

    private function createDocument(Workspace $workspace, array $data): Document
    {
        return Document::create([
            'workspace_id' => $workspace->id,
            'contact_id' => $data['contact_id'],
            'type' => DocumentType::Quotation,
            'document_subtype_id' => $data['document_subtype_id'],
            'status' => DocumentStatus::NonConverted,
            'document_number' => $data['ncf'],
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'currency_id' => 1, // TODO: Allow to switch currency...
            'total_amount' => $data['total'],
            'subtotal_amount' => $data['subtotal'],
            'discount_amount' => $data['discount_total'],
            'tax_amount' => $data['tax_amount'],
            'payment_term' => $data['payment_term'] ?? null,
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
     * }> $items
     */
    private function createQuotationItems(Document $document, array $items): void
    {
        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);

            $document->items()->create([
                'product_id' => $product->id,
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'discount_rate' => $item['discount_rate'] ?? 0,
                'tax_rate' => $item['tax_rate'] ?? 0,
                'tax_amount' => $item['tax_amount'] ?? 0,
                'tax_id' => 1, // @TODO: Pass the tax ID from the request
                'total' => $item['total'],
            ]);
        }
    }

    /**
     *  Updates the next number of the document type.
     */
    private function updateNumerator(DocumentSubtype $documentSubtype, string $ncf): void
    {
        $number = (int) ltrim(mb_substr($ncf, mb_strlen($documentSubtype->prefix)), '0');

        if ($number >= $documentSubtype->next_number) {
            $documentSubtype->update(['next_number' => $number + 1]);
        }
    }
}
