<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaxType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property numeric $rate
 * @property bool $is_default
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InvoiceItem> $invoiceItems
 * @property-read int|null $document_items_count
 * @property-read string $rate_percentage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tax default()
 * @method static \Database\Factories\TaxFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tax newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tax newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tax query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tax whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tax whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tax whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tax whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tax whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tax whereUpdatedAt($value)
 *
 * @property-read int|null $invoice_items_count
 *
 * @mixin \Eloquent
 */
final class Tax extends Model
{
    /** @use HasFactory<\Database\Factories\TaxFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'rate',
        'is_default',
    ];

    protected $appends = [
        'rate_percentage',
    ];

    /**
     * Get the products that use this tax as default.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'default_tax_id');
    }

    /**
     * Get the invoice items that use this tax (many-to-many).
     *
     * @return BelongsToMany<InvoiceItem, $this>
     */
    public function invoiceItems(): BelongsToMany
    {
        return $this->belongsToMany(InvoiceItem::class, 'invoice_item_tax')
            ->withPivot(['rate', 'amount'])
            ->withTimestamps();
    }

    /**
     * Get the quotation items that use this tax (many-to-many).
     *
     * @return BelongsToMany<QuotationItem, $this>
     */
    public function quotationItems(): BelongsToMany
    {
        return $this->belongsToMany(QuotationItem::class, 'quotation_item_tax')
            ->withPivot(['rate', 'amount'])
            ->withTimestamps();
    }

    /**
     * Check if the tax is in use (has invoice items, quotation items, or products).
     */
    public function isInUse(): bool
    {
        return $this->invoiceItems()->exists()
            || $this->quotationItems()->exists()
            || $this->products()->exists();
    }

    /**
     * Scope to get the default tax.
     */
    #[Scope]
    protected function default(Builder $query): void
    {
        $query->where('is_default', true);
    }

    /**
     * Get the tax rate as a percentage string.
     */
    protected function ratePercentage(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->rate . '%'
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TaxType::class,
            'rate' => 'decimal:2',
            'is_default' => 'boolean',
        ];
    }
}
