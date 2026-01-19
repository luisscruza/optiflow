<?php

declare(strict_types=1);

namespace App\Services\Automation\Support;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WorkflowJob;
use App\Models\WorkflowStage;

final readonly class AutomationContext
{
    public function __construct(
        public ?WorkflowJob $job,
        public ?WorkflowStage $fromStage,
        public ?WorkflowStage $toStage,
        public ?User $actor,
        public ?Invoice $invoice = null,
        public ?Contact $contact = null,
    ) {}

    /**
     * Template-accessible data.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function toTemplateData(array $input = []): array
    {
        $contact = $this->contact;
        $invoice = $this->invoice;

        if (! $contact && $this->job?->relationLoaded('contact')) {
            $contact = $this->job->contact;
        }

        if (! $invoice && $this->job?->relationLoaded('invoice')) {
            $invoice = $this->job->invoice;
        }

        $contactNumber = null;
        if ($contact) {
            $contactNumber = $contact->mobile
                ?? $contact->phone
                ?? $contact->phone_primary
                ?? $contact->phone_secondary;
        }
        
        \Log::debug('AutomationContext toTemplateData', [
            'input' => $input,
            'job_id' => $this->job?->id,
            'contact_id' => $contact?->id,
            'invoice_id' => $invoice?->total_amount,
        ]);

        return [
            'input' => $input,
            'job' => $this->job ? [
                'id' => $this->job->id,
                'workflow_id' => $this->job->workflow_id,
                'workflow_stage_id' => $this->job->workflow_stage_id,
                'contact_id' => $this->job->contact_id,
                'invoice_id' => $this->job->invoice_id,
                'notes' => $this->job->notes,
                'priority' => $this->job->priority,
                'due_date' => optional($this->job->due_date)?->toISOString(),
                'started_at' => optional($this->job->started_at)?->toISOString(),
                'completed_at' => optional($this->job->completed_at)?->toISOString(),
            ] : null,
            // Metadata fields are expanded to top level for easy access
            'metadata' => $this->job?->metadata ?? [],
            'contact' => $contact ? [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'phone_primary' => $contact->phone_primary,
                'phone_secondary' => $contact->phone_secondary,
                'mobile' => $contact->mobile,
                // Friendly alias for builders who think in "number".
                'number' => $contactNumber,
            ] : null,
            'invoice' => $invoice ? [
                'id' => $invoice->id,
                'document_number' => $invoice->document_number,
                // Friendly alias for builders who think in "number".
                'number' => $invoice->document_number,
                'total_amount' => $invoice->total_amount,
                'issue_date' => optional($invoice->issue_date)?->toISOString(),
                'due_date' => optional($invoice->due_date)?->toISOString(),
                'status' => $invoice->status,
            ] : null,
            'from_stage' => $this->fromStage ? [
                'id' => $this->fromStage->id,
                'name' => $this->fromStage->name,
            ] : null,
            'to_stage' => $this->toStage ? [
                'id' => $this->toStage->id,
                'name' => $this->toStage->name,
            ] : null,
            'actor' => $this->actor ? [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
                'email' => $this->actor->email,
            ] : null,
        ];
    }
}
