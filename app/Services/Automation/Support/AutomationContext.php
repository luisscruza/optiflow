<?php

declare(strict_types=1);

namespace App\Services\Automation\Support;

use App\Models\User;
use App\Models\WorkflowJob;
use App\Models\WorkflowStage;

final readonly class AutomationContext
{
    public function __construct(
        public WorkflowJob $job,
        public ?WorkflowStage $fromStage,
        public ?WorkflowStage $toStage,
        public ?User $actor,
    ) {}

    /**
     * Template-accessible data.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function toTemplateData(array $input = []): array
    {
        $contact = $this->job->relationLoaded('contact') ? $this->job->contact : null;
        $invoice = $this->job->relationLoaded('invoice') ? $this->job->invoice : null;

        $contactNumber = null;
        if ($contact) {
            $contactNumber = $contact->mobile
                ?? $contact->phone
                ?? $contact->phone_primary
                ?? $contact->phone_secondary;
        }

        return [
            'input' => $input,
            'job' => [
                'id' => $this->job->id,
                'workflow_id' => $this->job->workflow_id,
                'workflow_stage_id' => $this->job->workflow_stage_id,
                'contact_id' => $this->job->contact_id,
                'invoice_id' => $this->job->invoice_id,
                'prescription_id' => $this->job->prescription_id,
                'priority' => $this->job->priority,
                'due_date' => optional($this->job->due_date)?->toISOString(),
                'metadata' => $this->job->metadata,
                'workspace_id' => $this->job->workspace_id,
            ],
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
                'type' => $invoice->type,
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
