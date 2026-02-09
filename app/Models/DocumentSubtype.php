<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentType;
use App\Exceptions\ReportableActionException;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invoice> $documents
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
 * @property DocumentType $type
 *
 * @method static Builder<static>|DocumentSubtype forInvoice()
 * @method static Builder<static>|DocumentSubtype forQuotation()
 * @method static Builder<static>|DocumentSubtype whereType($value)
 *
 * @mixin \Eloquent
 */
final class DocumentSubtype extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentSubtypeFactory> */
    use HasFactory;

    /**
     * Get document subtype by prefix.
     */
    public static function findByPrefix(string $prefix): ?self
    {
        return self::query()->where('prefix', $prefix)->first();
    }

    /**
     * Check if the sequence is valid (not expired and within range).
     */
    public function isValid(): bool
    {
        if ($this->valid_until_date && $this->valid_until_date->isPast()) {
            return false;
        }

        return ! ($this->end_number && $this->next_number > $this->end_number);
    }

    /**
     * Get the next NCF number and increment the sequence.
     */
    public function getNextNcfNumber(): string
    {
        if (! $this->isValid()) {
            throw new ReportableActionException("La secuencia de NCF para {$this->name} es inválida o ha expirado. Por favor, actualice la configuración de NCF para este tipo de documento.");
        }

        $ncfNumber = $this->prefix.mb_str_pad((string) $this->next_number, 8, '0', STR_PAD_LEFT);

        // Increment the next number
        $this->increment('next_number');

        return $ncfNumber;
    }

    /**
     * Generate the next NCF number without incrementing (for preview).
     *
     * @throws Exception
     */
    public function generateNCF(): string
    {
        if (! $this->isValid()) {
            throw new ReportableActionException("La secuencia de NCF para {$this->name} es inválida o ha expirado. Por favor, actualice la configuración de NCF para este tipo de documento.");
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
     * @return HasMany<Invoice, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the workspaces that have preferences for this document subtype.
     *
     * @return BelongsToMany<Workspace, $this>
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class)
            ->withPivot(['is_preferred'])
            ->withTimestamps();
    }

    /**
     * Get workspaces where this document subtype is preferred.
     *
     * @return BelongsToMany<Workspace, $this>
     */
    public function preferredByWorkspaces(): BelongsToMany
    {
        return $this->workspaces()->wherePivot('is_preferred', true);
    }

    /**
     * Check if this document subtype is preferred for a specific workspace.
     */
    public function isPreferredForWorkspace(Workspace $workspace): bool
    {
        return $this->preferredByWorkspaces()
            ->where('workspaces.id', $workspace->id)
            ->exists();
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
        $query->where(function ($q): void {
            $q->whereNull('valid_until_date')
                ->orWhere('valid_until_date', '>', now());
        });
    }

    #[Scope]
    protected function forQuotation(Builder $query): void
    {
        $query->where('type', DocumentType::Quotation);
    }

    #[Scope]
    protected function forInvoice(Builder $query): void
    {
        $query->where('type', DocumentType::Invoice);
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'valid_until_date' => 'date',
            'start_number' => 'integer',
            'end_number' => 'integer',
            'next_number' => 'integer',
            'type' => DocumentType::class,
        ];
    }
}
