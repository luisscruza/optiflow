<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToWorkspace;
use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $contact_id
 * @property string $document_number
 * @property string|null $easyfactu_received_document_id
 * @property \Carbon\CarbonImmutable $issue_date
 * @property numeric $subtotal_amount
 * @property numeric $itbis_amount
 * @property numeric $isc_amount
 * @property numeric $withheld_itbis_amount
 * @property numeric $withheld_isr_amount
 * @property numeric $total_amount
 * @property bool $is_informal
 * @property ExpenseStatus $status
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Contact $contact
 * @property-read Workspace $workspace
 *
 * @method static \Database\Factories\ExpenseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense forWorkspace(\App\Models\Workspace|int $workspace)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereContactId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereEasyfactuReceivedDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereIssueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereWorkspaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense withoutWorkspaceScope()
 *
 * @mixin \Eloquent
 */
final class Expense extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use BelongsToWorkspace, HasFactory, InteractsWithMedia;

    protected $guarded = [];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ]);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function canBeEdited(): bool
    {
        return $this->status !== ExpenseStatus::Cancelled;
    }

    public function canBeDeleted(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'immutable_date',
            'subtotal_amount' => 'decimal:2',
            'itbis_amount' => 'decimal:2',
            'isc_amount' => 'decimal:2',
            'withheld_itbis_amount' => 'decimal:2',
            'withheld_isr_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_informal' => 'boolean',
            'status' => ExpenseStatus::class,
        ];
    }
}
