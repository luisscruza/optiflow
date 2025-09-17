<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $contact_id
 * @property string $type
 * @property int $document_subtype_id
 * @property string $status
 * @property string $document_number
 * @property \Carbon\CarbonImmutable $issue_date
 * @property \Carbon\CarbonImmutable|null $due_date
 * @property numeric $total_amount
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Contact $contact
 * @property-read DocumentSubtype $documentSubtype
 * @property-read float $subtotal
 * @property-read float $total_discount
 * @property-read float $total_tax
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DocumentItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMovement> $stockMovements
 * @property-read int|null $stock_movements_count
 * @property-read Workspace $workspace
 *
 * @method static \Database\Factories\DocumentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document forWorkspace(\App\Models\Workspace|int $workspace)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document invoices()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document quotations()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereContactId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDocumentSubtypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereIssueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereWorkspaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document withStatus(string $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document withoutWorkspaceScope()
 *
 * @mixin \Eloquent
 */
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
     * Scope to filter by document type.
     */
    #[Scope]
    protected function ofType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Scope to get invoices.
     */
    #[Scope]
    protected function invoices(Builder $query): void
    {
        $query->where('type', 'invoice');
    }

    /**
     * Scope to get quotations.
     */
    #[Scope]
    protected function quotations(Builder $query): void
    {
        $query->where('type', 'quotation');
    }

    /**
     * Scope to filter by status.
     */
    #[Scope]
    protected function withStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Scope to get overdue documents.
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['paid', 'cancelled']);
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
        ];
    }
}
