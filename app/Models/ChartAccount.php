<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChartAccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property ChartAccountType $type
 * @property int|null $parent_id
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_system
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read ChartAccount|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ChartAccount> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PaymentConcept> $paymentConcepts
 * @property-read int|null $payment_concepts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PaymentLine> $paymentLines
 * @property-read int|null $payment_lines_count
 *
 * @method static \Database\Factories\ChartAccountFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChartAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChartAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChartAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChartAccount active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChartAccount ofType(ChartAccountType $type)
 *
 * @mixin \Eloquent
 */
final class ChartAccount extends Model
{
    /** @use HasFactory<\Database\Factories\ChartAccountFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ChartAccount, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ChartAccount, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<PaymentConcept, $this>
     */
    public function paymentConcepts(): HasMany
    {
        return $this->hasMany(PaymentConcept::class);
    }

    /**
     * @return HasMany<PaymentLine, $this>
     */
    public function paymentLines(): HasMany
    {
        return $this->hasMany(PaymentLine::class);
    }

    /**
     * Scope a query to only include active accounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ChartAccount>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ChartAccount>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by account type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ChartAccount>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ChartAccount>
     */
    public function scopeOfType($query, ChartAccountType $type)
    {
        return $query->where('type', $type->value);
    }

    /**
     * Get the full hierarchical name including parent names.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_name.' > '.$this->name;
        }

        return $this->name;
    }

    protected function casts(): array
    {
        return [
            'type' => ChartAccountType::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
