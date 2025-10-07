<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $contact_id
 * @property string $type
 * @property string|null $province
 * @property string|null $municipality
 * @property string|null $country
 * @property string|null $description
 * @property bool $is_primary
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Contact $contact
 * @property-read string|null $full_address
 *
 * @method static Builder<static>|Address newModelQuery()
 * @method static Builder<static>|Address newQuery()
 * @method static Builder<static>|Address primary()
 * @method static Builder<static>|Address query()
 * @method static Builder<static>|Address whereContactId($value)
 * @method static Builder<static>|Address whereCountry($value)
 * @method static Builder<static>|Address whereCreatedAt($value)
 * @method static Builder<static>|Address whereDescription($value)
 * @method static Builder<static>|Address whereId($value)
 * @method static Builder<static>|Address whereIsPrimary($value)
 * @method static Builder<static>|Address whereMunicipality($value)
 * @method static Builder<static>|Address whereProvince($value)
 * @method static Builder<static>|Address whereType($value)
 * @method static Builder<static>|Address whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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

                return $parts === [] ? null : implode(', ', $parts);
            }
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}
