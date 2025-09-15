<?php

declare(strict_types=1);

namespace App\Models;

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
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Get the full address as a single string.
     */
    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->description,
            $this->municipality,
            $this->province,
            $this->country,
        ]);

        return empty($parts) ? null : implode(', ', $parts);
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}
