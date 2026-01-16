<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToWorkspace;
use App\Concerns\HasActivityLog;
use App\Concerns\HasComments;
use App\Contracts\Auditable;
use App\Contracts\Commentable;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $contact_id
 * @property string $type
 * @property int $document_subtype_id
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $comments
 * @property-read int|null $comments_count
 * @property-read float $amount_due
 * @property-read InvoiceStatus $status
 * @property-read array<string, mixed> $status_config
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Payment> $payments
 * @property-read int|null $payments_count
 *
 * @mixin \Eloquent
 */
final class Invoice extends Model implements Auditable, Commentable
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use BelongsToWorkspace, HasActivityLog, HasComments, HasFactory;

    protected $appends = [
        'amount_due',
        'human_readable_issue_date',
    ];

    /**
     * Get the contact for this invoice.
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
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get stock movements related to this invoice.
     *
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'related_invoice_id');
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
     * Get the payments associated with the invoice.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the salesmen associated with the invoice.
     *
     * @return BelongsToMany<Salesman, $this>
     */
    public function salesmen(): BelongsToMany
    {
        return $this->belongsToMany(Salesman::class, 'invoice_salesman');
    }

    public function updatePaymentStatus(): void
    {
        if ($this->payments()->sum('amount') >= $this->total_amount) {
            $this->update(['status' => InvoiceStatus::Paid->value]);
        } elseif ($this->payments()->sum('amount') > 0) {
            $this->update(['status' => InvoiceStatus::PartiallyPaid->value]);
        } else {
            $this->update(['status' => InvoiceStatus::PendingPayment->value]);
        }
    }

    public function humanReadableIssueDate(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->issue_date->translatedFormat('d F Y'),
        );
    }

    /**
     * Get the activity log options for this model.
     * Only track meaningful changes, not calculated fields.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'contact_id',
                'document_subtype_id',
                'document_number',
                'issue_date',
                'due_date',
                'payment_term',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Factura creada',
                'updated' => 'Factura actualizada',
                'deleted' => 'Factura eliminada',
                default => "Factura {$eventName}",
            });
    }

    /**
     * Get human-readable field names for activity log display.
     *
     * @return array<string, string>
     */
    public function getActivityFieldLabels(): array
    {
        return [
            'contact_id' => 'Cliente',
            'document_subtype_id' => 'Tipo de documento',
            'document_number' => 'Número',
            'issue_date' => 'Fecha de emisión',
            'due_date' => 'Fecha de vencimiento',
            'payment_term' => 'Plazo de pago',
            'notes' => 'Notas',
        ];
    }

    /**
     * Determine if the invoice can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return ! in_array($this->status, [InvoiceStatus::Paid, InvoiceStatus::PartiallyPaid, InvoiceStatus::Deleted]);
    }

    /**
     * Determine if the invoice can be edited.
     */
    public function canBeEdited(): bool
    {
        return ! in_array($this->status, [InvoiceStatus::Paid, InvoiceStatus::PartiallyPaid, InvoiceStatus::Deleted, InvoiceStatus::Cancelled]);
    }

    /**
     * Determine if the invoice can register a payment.
     */
    public function canRegisterPayment(): bool
    {
        return ! in_array($this->status, [InvoiceStatus::Paid, InvoiceStatus::Deleted, InvoiceStatus::Cancelled, InvoiceStatus::Draft]);
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
     * Get the status attribute.
     */
    protected function amountDue(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: fn (): float|int => max(0, $this->total_amount - $this->payments()->sum('amount')));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'status' => InvoiceStatus::class,
        ];
    }
}
