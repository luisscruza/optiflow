<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\InvoiceResult;
use App\Enums\DocumentType;
use App\Exceptions\InsufficientStockException;
use App\Models\Document;
use App\Models\DocumentSubtype;
use App\Models\Workspace;
use App\Support\NCFValidator;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateInvoiceAction
{
    public function __construct(private CreateDocumentItemAction $createItems)
    {
        //
    }

    /**
     * @throws Throwable
     */
    public function handle(Workspace $workspace, array $data): InvoiceResult
    {
        return DB::transaction(function () use ($workspace, $data) {

            $documentSubtype = DocumentSubtype::findOrFail($data['document_subtype_id']);

            if (! NCFValidator::validate($data['ncf'], $documentSubtype, $data)) {
                return new InvoiceResult(error: 'El NCF proporcionado no es válido.');
            }

            $document = $this->createDocument($workspace, $data);

            $this->updateNumerator($documentSubtype, $data['ncf']);

            $items = array_filter($data['items'], function ($item) {
                return isset($item['product_id'], $item['quantity'], $item['unit_price']) &&
                    $item['quantity'] > 0;
            });

            try {
                $this->createItems->handle($document, $items);
            } catch (InsufficientStockException $e) {
                DB::rollBack();

                return new InvoiceResult(
                    error: $e->getMessage(),
                );
            }

            return new InvoiceResult(
                invoice: $document->load(['contact', 'documentSubtype', 'items.product']));
        });
    }

    private function createDocument(Workspace $workspace, array $data): Document
    {
        return Document::create([
            'workspace_id' => $workspace->id,
            'contact_id' => $data['contact_id'],
            'type' => DocumentType::Invoice,
            'document_subtype_id' => $data['document_subtype_id'],
            'status' => 'draft',
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
