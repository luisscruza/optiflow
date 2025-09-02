<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $fillable = [
        'workspace_id',
        'contact_id',
        'type',
        'document_subtype_id',
        'status',
        'document_number',
        'issue_date',
        'due_date',
        'total_amount',
        'notes',
    ];

    /**
     * Get the contact for this document.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the document subtype.
     *
     * @return BelongsTo<DocumentSubtype, $this>
     */
    public function documentSubtype(): BelongsTo
    {
        return $this->belongsTo(DocumentSubtype::class);
    }

    /**
     * Get the document items.
     *
     * @return HasMany<DocumentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    /**
     * Get stock movements related to this document.
     *
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'related_document_id');
    }

    /**
     * Scope to filter by document type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get invoices.
     */
    public function scopeInvoices($query)
    {
        return $query->where('type', 'invoice');
    }

    /**
     * Scope to get quotations.
     */
    public function scopeQuotations($query)
    {
        return $query->where('type', 'quotation');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get overdue documents.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['paid', 'cancelled']);
    }

    /**
     * Recalculate the total amount from items.
     */
    public function recalculateTotal(): void
    {
        $total = $this->items()->sum('total');

        if ($this->total_amount !== $total) {
            $this->update(['total_amount' => $total]);
        }
    }

    /**
     * Check if document is an invoice.
     */
    public function isInvoice(): bool
    {
        return $this->type === 'invoice';
    }

    /**
     * Check if document is a quotation.
     */
    public function isQuotation(): bool
    {
        return $this->type === 'quotation';
    }

    /**
     * Check if document is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date &&
               $this->due_date < now()->toDateString() &&
               ! in_array($this->status, ['paid', 'cancelled']);
    }

    /**
     * Check if document is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if document is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Mark document as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['status' => 'sent']);
    }

    /**
     * Mark document as paid.
     */
    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
    }

    /**
     * Convert quotation to invoice.
     */
    public function convertToInvoice(): ?self
    {
        if (! $this->isQuotation() || $this->status !== 'approved') {
            return null;
        }

        $invoiceSubtype = DocumentSubtype::invoice();
        if (! $invoiceSubtype) {
            return null;
        }

        $invoice = $this->replicate();
        $invoice->type = 'invoice';
        $invoice->document_subtype_id = $invoiceSubtype->id;
        $invoice->status = 'draft';
        $invoice->document_number = $invoiceSubtype->getNextDocumentNumber();
        $invoice->save();

        // Copy items
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->document_id = $invoice->id;
            $newItem->save();
        }

        $invoice->recalculateTotal();

        return $invoice;
    }

    /**
     * Get the subtotal (before tax).
     */
    public function getSubtotalAttribute(): float
    {
        return $this->items()->sum(function ($item) {
            $lineTotal = $item->quantity * $item->unit_price;

            return $lineTotal - ($lineTotal * $item->discount / 100);
        });
    }

    /**
     * Get the total tax amount.
     */
    public function getTotalTaxAttribute(): float
    {
        return $this->items()->sum(function ($item) {
            $lineSubtotal = $item->quantity * $item->unit_price;
            $lineSubtotalAfterDiscount = $lineSubtotal - ($lineSubtotal * $item->discount / 100);

            return $lineSubtotalAfterDiscount * $item->tax_rate_snapshot / 100;
        });
    }

    /**
     * Get the total discount amount.
     */
    public function getTotalDiscountAttribute(): float
    {
        return $this->items()->sum(function ($item) {
            $lineTotal = $item->quantity * $item->unit_price;

            return $lineTotal * $item->discount / 100;
        });
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $document) {
            // Auto-generate document number if not provided
            if (! $document->document_number && $document->documentSubtype) {
                $document->document_number = $document->documentSubtype->getNextDocumentNumber();
            }

            // Set default issue date
            if (! $document->issue_date) {
                $document->issue_date = now()->toDateString();
            }
        });

        self::saved(function (self $document) {
            // Recalculate total when document is saved
            $document->recalculateTotal();
        });
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }
}
