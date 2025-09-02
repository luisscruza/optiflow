<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Tax extends Model
{
    /** @use HasFactory<\Database\Factories\TaxFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'is_default',
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
     * Get the document items that use this tax.
     *
     * @return HasMany<DocumentItem, $this>
     */
    public function documentItems(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    /**
     * Scope to get the default tax.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the tax rate as a percentage string.
     */
    public function getRatePercentageAttribute(): string
    {
        return $this->rate.'%';
    }

    /**
     * Calculate tax amount for a given subtotal.
     */
    public function calculateTaxAmount(float $subtotal): float
    {
        return round(($subtotal * $this->rate) / 100, 2);
    }

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_default' => 'boolean',
        ];
    }
}
