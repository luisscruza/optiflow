<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
