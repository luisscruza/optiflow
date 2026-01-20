<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToWorkspace;
use App\Concerns\HasComments;
use App\Contracts\Commentable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class WorkflowJob extends Model implements Commentable, HasMedia
{
    use BelongsToWorkspace;
    use HasComments;
    use HasUuids;
    use InteractsWithMedia;

    /**
     * Register the media collections for this model.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useFallbackUrl('/images/placeholder.png')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }

    /**
     * Register the media conversions for this model.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $thumbConversion = $this->addMediaConversion('thumb');
        $thumbConversion->performOnCollections('images');
        $thumbConversion
            ->width(150)
            ->height(150)
            ->sharpen(10);

        $previewConversion = $this->addMediaConversion('preview');
        $previewConversion->performOnCollections('images');
        $previewConversion
            ->width(800)
            ->height(600);
    }

    /**
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * @return BelongsTo<WorkflowStage, $this>
     */
    public function workflowStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Prescription, $this>
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * @return HasMany<WorkflowEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(WorkflowEvent::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contact_id' => 'integer',
            'invoice_id' => 'integer',
            'prescription_id' => 'integer',
            'notes' => 'string',
            'metadata' => 'array',
            'priority' => 'string',
            'due_date' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'canceled_at' => 'datetime',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
