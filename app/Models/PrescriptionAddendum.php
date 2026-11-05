<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PrescriptionAddendumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $prescription_id
 * @property int|null $optometrist_id
 * @property int|null $user_id
 * @property string $content
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 */
final class PrescriptionAddendum extends Model
{
    /** @use HasFactory<PrescriptionAddendumFactory> */
    use HasFactory;

    protected $table = 'prescription_addenda';

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
}
