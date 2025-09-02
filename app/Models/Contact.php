<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string $contact_type
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Document> $documents
 * @property-read int|null $documents_count
 * @property-read string|null $full_address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Document> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Document> $quotations
 * @property-read int|null $quotations_count
 * @property-read Workspace $workspace
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact customers()
 * @method static \Database\Factories\ContactFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact forWorkspace(\App\Models\Workspace|int $workspace)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact suppliers()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereContactType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereWorkspaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact withoutWorkspaceScope()
 *
 * @mixin \Eloquent
 */
final class Contact extends Model
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'email',
        'phone',
        'address',
        'contact_type',
        'metadata',
    ];

    /**
     * Get the documents for this contact.
     *
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the invoices for this contact.
     *
     * @return HasMany<Document, $this>
     */
    public function invoices(): HasMany
    {
        return $this->documents()->where('type', 'invoice');
    }

    /**
     * Get the quotations for this contact.
     *
     * @return HasMany<Document, $this>
     */
    public function quotations(): HasMany
    {
        return $this->documents()->where('type', 'quotation');
    }

    /**
     * Scope to filter by contact type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('contact_type', $type);
    }

    /**
     * Scope to get customers.
     */
    public function scopeCustomers($query)
    {
        return $query->whereIn('contact_type', ['customer', 'both']);
    }

    /**
     * Scope to get suppliers.
     */
    public function scopeSuppliers($query)
    {
        return $query->whereIn('contact_type', ['supplier', 'both']);
    }

    /**
     * Check if this contact is a customer.
     */
    public function isCustomer(): bool
    {
        return in_array($this->contact_type, ['customer', 'both']);
    }

    /**
     * Check if this contact is a supplier.
     */
    public function isSupplier(): bool
    {
        return in_array($this->contact_type, ['supplier', 'both']);
    }

    /**
     * Get the full address as a single string.
     */
    public function getFullAddressAttribute(): ?string
    {
        return $this->address;
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
