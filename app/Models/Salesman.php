<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $surname
 * @property int|null $user_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read string $full_name
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 *
 * @method static \Database\Factories\SalesmanFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Salesman newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Salesman newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Salesman query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Salesman whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Salesman whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Salesman whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Salesman whereSurname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Salesman whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Salesman whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class Salesman extends Model
{
    /** @use HasFactory<\Database\Factories\SalesmanFactory> */
    use HasFactory;

    protected $appends = ['full_name'];

    /**
     * Get the user associated with this salesman.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the invoices for this salesman.
     *
     * @return BelongsToMany<Invoice, $this>
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'invoice_salesman');
    }

    /**
     * Get the full name attribute.
     */
    protected function fullName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (): string => $this->name.' '.$this->surname
        );
    }
}
