<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentSubtype;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

final readonly class CreateInvoiceAction
{
    public function handle(array $data): Document
    {
        return DB::transaction(function () use ($data) {
            // Get the document subtype to generate NCF
            $documentSubtype = DocumentSubtype::findOrFail($data['document_subtype_id']);

            // Generate NCF and increment sequence
            $ncf = $documentSubtype->getNextNcfNumber();

            // Create the main document
            $document = Document::create([
                'type' => 'invoice',
                'document_subtype_id' => $data['document_subtype_id'],
                'contact_id' => $data['contact_id'],
                'document_number' => $ncf, // NCF is stored in document_number
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'total_amount' => $data['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Create document items and update stock
            foreach ($data['items'] as $itemData) {
                DocumentItem::create([
                    'document_id' => $document->id,
                    'product_id' => $itemData['product_id'],
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount' => 0, // No discount for now
                    'tax_id' => $itemData['tax_id'] ?? null,
                    'tax_rate_snapshot' => $itemData['tax_rate'],
                    'total' => $itemData['total'],
                ]);

                // Update product stock
                if ($itemData['product_id']) {
                    $product = Product::findOrFail($itemData['product_id']);
                    if ($product->track_stock) {
                        $product->decrement('stock', $itemData['quantity']);
                    }
                }
            }

            return $document->load(['contact', 'documentSubtype', 'items.product']);
        });
    }

    /**
     * Generate NCF for a given document subtype (for partial reloads)
     */
    public function generateNCF(int $documentSubtypeId): string
    {
        $documentSubtype = DocumentSubtype::findOrFail($documentSubtypeId);

        return $documentSubtype->generateNCF();
    }
}
