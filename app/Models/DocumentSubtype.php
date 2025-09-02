<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    protected function casts(): array
    {
        return [
            'sequence' => 'string',
        ];
    }
}
