<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $sequence
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Document> $documents
 * @property-read int|null $documents_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype byName(string $name)
 * @method static \Database\Factories\DocumentSubtypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereSequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentSubtype whereUpdatedAt($value)
 * @mixin \Eloquent
 */
final class DocumentSubtype extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentSubtypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'sequence',
    ];

    /**
     * Get invoices subtype.
     */
    public static function invoice(): ?self
    {
        return self::byName('Invoice')->first();
    }

    /**
     * Get quotations subtype.
     */
    public static function quotation(): ?self
    {
        return self::byName('Quotation')->first();
    }

    /**
     * Get credit note subtype.
     */
    public static function creditNote(): ?self
    {
        return self::byName('Credit Note')->first();
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
     * Generate the next document number for this subtype.
     */
    public function getNextDocumentNumber(): string
    {
        $lastDocument = $this->documents()
            ->latest('id')
            ->first();

        if (! $lastDocument) {
            return $this->name.'-'.mb_str_pad('1', 4, '0', STR_PAD_LEFT);
        }

        // Extract the number from the last document number
        $lastNumber = (int) mb_substr($lastDocument->document_number, mb_strrpos($lastDocument->document_number, '-') + 1);
        $nextNumber = $lastNumber + 1;

        return $this->name.'-'.mb_str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope to get subtypes by name.
     */
    #[Scope]
    protected function byName(Builder $query, string $name): void
    {
        $query->where('name', $name);
    }

    protected function casts(): array
    {
        return [
            'sequence' => 'string',
        ];
    }
}
