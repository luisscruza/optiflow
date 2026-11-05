<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $prescription_id
 * @property int|null $optometrist_id
 * @property int|null $user_id
 * @property \Carbon\CarbonImmutable $closed_at
 */
final class PrescriptionClosure extends Model
{
    /**
     * @return BelongsTo<Prescription, $this>
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function optometrist(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'optometrist_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }
}
