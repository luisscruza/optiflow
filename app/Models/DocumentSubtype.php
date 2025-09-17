<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_default
 * @property \Carbon\CarbonImmutable|null $valid_until_date
 * @property string $prefix
 * @property int $start_number
 * @property int|null $end_number
 * @property int $next_number
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Document> $documents
 * @property-read int|null $documents_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype byName(string $name)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype byPrefix(string $prefix)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype default()
 * @method static \Database\Factories\DocumentSubtypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereValidUntilDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype wherePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereStartNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereEndNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereNextNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class DocumentSubtype extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentSubtypeFactory> */
    use HasFactory;

    /**
     * Get default document subtype.
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->first();
    }

    /**
     * Get document subtype by prefix.
     */
    public static function findByPrefix(string $prefix): ?self
    {
        return self::where('prefix', $prefix)->first();
    }

    /**
     * Check if the sequence is valid (not expired and within range).
     */
    public function isValid(): bool
    {
        // Check if not expired
        if ($this->valid_until_date && $this->valid_until_date->isPast()) {
            return false;
        }

        // Check if within range
        if ($this->end_number && $this->next_number > $this->end_number) {
            return false;
        }

        return true;
    }

    /**
     * Get the next NCF number and increment the sequence.
     */
    public function getNextNcfNumber(): string
    {
        if (! $this->isValid()) {
            throw new Exception("NCF sequence for {$this->name} is invalid or expired");
        }

        $ncfNumber = $this->prefix.mb_str_pad((string) $this->next_number, 8, '0', STR_PAD_LEFT);

        // Increment the next number
        $this->increment('next_number');

        return $ncfNumber;
    }

    /**
     * Generate the next NCF number without incrementing (for preview).
     */
    public function generateNCF(): string
    {
        if (! $this->isValid()) {
            throw new Exception("NCF sequence for {$this->name} is invalid or expired");
        }

        return $this->prefix.mb_str_pad((string) $this->next_number, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Check if sequence is near expiration (within 30 days).
     */
    public function isNearExpiration(): bool
    {
        if (! $this->valid_until_date) {
            return false;
        }

        return $this->valid_until_date->diffInDays(now()) <= 30;
    }

    /**
     * Check if sequence is running low (less than 100 numbers remaining).
     */
    public function isRunningLow(): bool
    {
        if (! $this->end_number) {
            return false;
        }

        return ($this->end_number - $this->next_number) < 100;
    }

    /**
     * Get the documents of this subtype.
     *
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Generate the next document number for this subtype (legacy method).
     *
     * @deprecated Use getNextNcfNumber() instead for NCF compliance.
     */
    public function getNextDocumentNumber(): string
    {
        return $this->getNextNcfNumber();
    }

    /**
     * Scope to get subtypes by name.
     */
    #[Scope]
    protected function byName(Builder $query, string $name): void
    {
        $query->where('name', $name);
    }

    /**
     * Scope to get active (valid) subtypes.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where(function ($q) {
            $q->whereNull('valid_until_date')
                ->orWhere('valid_until_date', '>', now());
        })->where(function ($q) {
            $q->whereNull('end_number')
                ->orWhereColumn('next_number', '<=', 'end_number');
        });
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'valid_until_date' => 'date',
            'start_number' => 'integer',
            'end_number' => 'integer',
            'next_number' => 'integer',
        ];
    }
}
