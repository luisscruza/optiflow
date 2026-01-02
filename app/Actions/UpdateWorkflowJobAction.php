<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\WorkflowJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final readonly class UpdateWorkflowJobAction
{
    public function __construct(
        private RecordWorkflowEventAction $recordEvent,
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{invoice_id?: int, contact_id?: int, priority?: string|null, due_date?: string|null, started_at?: string|null, completed_at?: string|null, canceled_at?: string|null, metadata?: array<string, mixed>, images?: array<UploadedFile>, remove_images?: array<int>}  $data
     */
    public function handle(WorkflowJob $job, array $data): WorkflowJob
    {
        return DB::transaction(function () use ($job, $data): WorkflowJob {
            $oldPriority = $job->priority;
            $oldMetadata = $job->metadata ?? [];

            $metadata = $oldMetadata;
            if (array_key_exists('metadata', $data)) {
                $metadata = array_merge($metadata, $data['metadata'] ?? []);
            }

            $job->update([
                'invoice_id' => $data['invoice_id'] ?? $job->invoice_id,
                'contact_id' => $data['contact_id'] ?? $job->contact_id,
                'priority' => array_key_exists('priority', $data) ? $data['priority'] : $job->priority,
                'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $job->due_date,
                'started_at' => array_key_exists('started_at', $data) ? $data['started_at'] : $job->started_at,
                'completed_at' => array_key_exists('completed_at', $data) ? $data['completed_at'] : $job->completed_at,
                'canceled_at' => array_key_exists('canceled_at', $data) ? $data['canceled_at'] : $job->canceled_at,
                'metadata' => $metadata,
            ]);

            if (array_key_exists('priority', $data) && $oldPriority !== $data['priority']) {
                $this->recordEvent->priorityUpdated($job, $oldPriority, $data['priority']);
            }

            // Record metadata change event if metadata was updated
            if (array_key_exists('metadata', $data) && $data['metadata']) {
                $changedFields = [];
                foreach ($data['metadata'] as $key => $value) {
                    $oldValue = $oldMetadata[$key] ?? null;
                    if ($oldValue !== $value) {
                        $changedFields[$key] = [
                            'from' => $oldValue,
                            'to' => $value,
                        ];
                    }
                }

                if (! empty($changedFields)) {
                    $this->recordEvent->metadataUpdated($job, $changedFields);
                }
            }

            if (isset($data['remove_images']) && is_array($data['remove_images'])) {
                $this->removeImages($job, $data['remove_images']);
            }

            if (isset($data['images']) && is_array($data['images'])) {
                $this->addImages($job, $data['images']);
            }

            return $job->fresh();
        });
    }

    /**
     * Add images to the workflow job.
     *
     * @param  array<UploadedFile>  $images
     */
    private function addImages(WorkflowJob $job, array $images): void
    {
        $fileNames = [];

        foreach ($images as $image) {
            $job->addMedia($image)
                ->toMediaCollection('images');
            $fileNames[] = $image->getClientOriginalName();
        }

        if (! empty($fileNames)) {
            $this->recordEvent->imagesAdded($job, $fileNames);
        }
    }

    /**
     * Remove images from the workflow job.
     *
     * @param  array<int>  $mediaIds
     */
    private function removeImages(WorkflowJob $job, array $mediaIds): void
    {
        $mediaToDelete = $job->media()
            ->whereIn('id', $mediaIds)
            ->where('collection_name', 'images')
            ->get();

        $fileNames = $mediaToDelete->pluck('file_name')->toArray();

        $mediaToDelete->each(fn($media) => $media->delete());

        if (! empty($fileNames)) {
            $this->recordEvent->imagesRemoved($job, $fileNames);
        }
    }
}
