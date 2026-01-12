<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToWorkspace;
use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Builder;
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InvoiceItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMovement> $stockMovements
 * @property-read int|null $stock_movements_count
 * @property-read Workspace $workspace
 *
 * @method static \Database\Factories\InvoiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice forWorkspace(\App\Models\Workspace|int $workspace)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice invoices()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice quotations()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereContactId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDocumentSubtypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereIssueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereWorkspaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withStatus(string $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withoutWorkspaceScope()
 *
 * @property int|null $created_by
 * @property int|null $currency_id
 * @property float $tax_amount
 * @property float $discount_amount
 * @property float $subtotal_amount
 * @property string|null $payment_term
 *
 * @method static Builder<static>|Invoice whereCreatedBy($value)
 * @method static Builder<static>|Invoice whereCurrencyId($value)
 * @method static Builder<static>|Invoice whereDiscountAmount($value)
 * @method static Builder<static>|Invoice wherePaymentTerm($value)
 * @method static Builder<static>|Invoice whereSubtotalAmount($value)
 * @method static Builder<static>|Invoice whereTaxAmount($value)
 *
 * @mixin \Eloquent
 */
final class Quotation extends Model
{
    use BelongsToWorkspace;

    /**
     * Get the contact for this quotation.
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
     * Get the invoice items.
     *
     * @return HasMany<QuotationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
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

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'status' => QuotationStatus::class,
        ];
    }
}
