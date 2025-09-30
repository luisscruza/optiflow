<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasComments;
use App\Contracts\Commentable;
use App\Enums\ContactType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invoice> $documents
 * @property-read int|null $documents_count
 * @property-read string|null $full_address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invoice> $quotations
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
 * @property string|null $phone_primary
 * @property string|null $phone_secondary
 * @property string|null $mobile
 * @property string|null $fax
 * @property string|null $identification_type
 * @property string|null $identification_number
 * @property string $status
 * @property string|null $observations
 * @property numeric $credit_limit
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Address> $addresses
 * @property-read int|null $addresses_count
 * @property-read array|null $identification_object
 * @property-read Address|null $primaryAddress
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductStock> $suppliedStocks
 * @property-read int|null $supplied_stocks_count
 *
 * @method static Builder<static>|Contact whereCreditLimit($value)
 * @method static Builder<static>|Contact whereFax($value)
 * @method static Builder<static>|Contact whereIdentificationNumber($value)
 * @method static Builder<static>|Contact whereIdentificationType($value)
 * @method static Builder<static>|Contact whereMobile($value)
 * @method static Builder<static>|Contact whereObservations($value)
 * @method static Builder<static>|Contact wherePhonePrimary($value)
 * @method static Builder<static>|Contact wherePhoneSecondary($value)
 * @method static Builder<static>|Contact whereStatus($value)
 *
 * @mixin \Eloquent
 */
final class Contact extends Model implements Commentable
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory, HasComments;

    protected $fillable = [
        'name',
        'identification_type',
        'identification_number',
        'email',
        'phone_primary',
        'phone_secondary',
        'mobile',
        'fax',
        'contact_type',
        'status',
        'observations',
        'credit_limit',
        'metadata',
    ];

    protected $appends = [
        'identification_object',
        'full_address',
    ];

    /**
     * Get the addresses for this contact.
     *
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the primary address for this contact.
     */
    public function primaryAddress(): HasOne
    {
        return $this->addresses()->one()->where('is_primary', true);
    }

    /**
     * Get the documents for this contact.
     *
     * @return HasMany<Invoice, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the invoices for this contact.
     *
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->documents()->where('type', 'invoice');
    }

    /**
     * Get the quotations for this contact.
     *
     * @return HasMany<Invoice, $this>
     */
    public function quotations(): HasMany
    {
        return $this->documents()->where('type', 'quotation');
    }

    /**
     * Get the product stocks supplied by this contact.
     *
     * @return HasMany<ProductStock, $this>
     */
    public function suppliedStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'supplier_id');
    }

    /**
     * Check if this contact is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->contact_type === ContactType::Customer->value;
    }

    /**
     * Check if this contact is a supplier.
     */
    public function isSupplier(): bool
    {
        return $this->contact_type === ContactType::Supplier->value;
    }

    /**
     * Check if this contact is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope to filter by contact type.
     */
    #[Scope]
    protected function ofType(Builder $query, string $type): void
    {
        $query->where('contact_type', $type);
    }

    /**
     * Scope to get customers.
     */
    #[Scope]
    protected function customers(Builder $query): void
    {
        $query->where('contact_type', ContactType::Customer->value);
    }

    /**
     * Scope to get suppliers.
     */
    #[Scope]
    protected function suppliers(Builder $query): void
    {
        $query->where('contact_type', ContactType::Supplier->value);
    }

    /**
     * Get the identification object in Alegra format.
     */
    protected function identificationObject(): Attribute
    {
        return Attribute::make(
            get: fn (): ?array => $this->identification_type && $this->identification_number
                ? [
                    'type' => mb_strtoupper($this->identification_type),
                    'number' => $this->identification_number,
                ]
                : null
        );
    }

    /**
     * Get the full address as a single string.
     */
    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->primaryAddress?->full_address
        );
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'credit_limit' => 'decimal:2',
        ];
    }
}
