<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'type',
        'province',
        'municipality',
        'country',
        'description',
        'is_primary',
    ];

    protected $appends = [
        'full_address',
    ];

    /**
     * Get the contact that owns this address.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Scope to get primary addresses.
     */
    #[Scope]
    protected function primary(Builder $query): void
    {
        $query->where('is_primary', true);
    }

    /**
     * Get the full address as a single string.
     */
    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $parts = array_filter([
                    $this->description,
                    $this->municipality,
                    $this->province,
                    $this->country,
                ]);

                return empty($parts) ? null : implode(', ', $parts);
            }
        );
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}
