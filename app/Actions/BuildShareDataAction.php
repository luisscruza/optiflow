<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ShareTemplateChannel;
use App\Enums\ShareTemplateEntity;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Prescription;
use App\Models\Quotation;
use App\Models\ShareTemplate;
use App\Services\Automation\Support\TemplateRenderer;
use Illuminate\Support\Facades\URL;

final class BuildShareDataAction
{
    public function __construct(private GetShareTemplateVariableGroupsAction $getShareTemplateVariableGroupsAction) {}

    /**
     * @return array<string, mixed>
     */
    public function forInvoice(Invoice $invoice): array
    {
        $shareableLink = URL::temporarySignedRoute('shared.invoices.pdf', now()->addDays(30), ['invoice' => $invoice]);

        return $this->build(
            entity: ShareTemplateEntity::Invoice,
            contact: $invoice->contact,
            shareableLink: $shareableLink,
            context: [
                'shareable_link' => $shareableLink,
                'contact' => $this->contactData($invoice->contact),
                'workspace' => ['name' => $invoice->workspace?->name ?? ''],
                'invoice' => [
                    'id' => $invoice->id,
                    'document_number' => $invoice->document_number,
                    'issue_date' => $this->formatDate($invoice->issue_date),
                    'due_date' => $this->formatDate($invoice->due_date),
                    'total_amount' => $this->formatAmount($invoice->total_amount),
                ],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forQuotation(Quotation $quotation): array
    {
        $shareableLink = URL::temporarySignedRoute('shared.quotations.pdf', now()->addDays(30), ['quotation' => $quotation]);

        return $this->build(
            entity: ShareTemplateEntity::Quotation,
            contact: $quotation->contact,
            shareableLink: $shareableLink,
            context: [
                'shareable_link' => $shareableLink,
                'contact' => $this->contactData($quotation->contact),
                'workspace' => ['name' => $quotation->workspace?->name ?? ''],
                'quotation' => [
                    'id' => $quotation->id,
                    'document_number' => $quotation->document_number,
                    'issue_date' => $this->formatDate($quotation->issue_date),
                    'due_date' => $this->formatDate($quotation->due_date),
                    'total_amount' => $this->formatAmount($quotation->total_amount),
                ],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forPrescription(Prescription $prescription): array
    {
        $shareableLink = URL::temporarySignedRoute('shared.prescriptions.pdf', now()->addDays(30), ['prescription' => $prescription]);

        return $this->build(
            entity: ShareTemplateEntity::Prescription,
            contact: $prescription->patient,
            shareableLink: $shareableLink,
            context: [
                'shareable_link' => $shareableLink,
                'contact' => $this->contactData($prescription->patient),
                'patient' => $this->contactData($prescription->patient),
                'workspace' => ['name' => $prescription->workspace?->name ?? ''],
                'prescription' => [
                    'id' => $prescription->id,
                    'created_at' => $this->formatDate($prescription->created_at),
                    'next_control_date' => $this->formatDate($prescription->proximo_control_visual),
                ],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function build(ShareTemplateEntity $entity, Contact $contact, string $shareableLink, array $context): array
    {
        $variableGroups = $this->getShareTemplateVariableGroupsAction->handle();

        return [
            'entityType' => $entity->value,
            'shareableLink' => $shareableLink,
            'variables' => $variableGroups[$entity->value] ?? [],
            'targets' => [
                'email' => $contact->email,
                'phone' => $contact->phone_primary,
            ],
            'templates' => [
                'email' => $this->renderTemplate($entity, ShareTemplateChannel::Email, $context),
                'whatsapp' => $this->renderTemplate($entity, ShareTemplateChannel::WhatsApp, $context),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    private function renderTemplate(ShareTemplateEntity $entity, ShareTemplateChannel $channel, array $context): ?array
    {
        $template = ShareTemplate::query()
            ->where('entity_type', $entity->value)
            ->where('channel', $channel->value)
            ->where('is_active', true)
            ->first();

        if (! $template instanceof ShareTemplate) {
            return null;
        }

        return [
            'id' => $template->id,
            'name' => $template->name,
            'channel' => $template->channel->value,
            'subject' => $template->subject !== null ? TemplateRenderer::renderString($template->subject, $context) : null,
            'body' => TemplateRenderer::renderString($template->body, $context),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function contactData(Contact $contact): array
    {
        return [
            'name' => $contact->name,
            'email' => $contact->email ?? '',
            'phone' => $contact->phone_primary ?? '',
        ];
    }

    private function formatAmount(int|float|string|null $amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }

    private function formatDate(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return now()->parse((string) $date)->locale('es')->translatedFormat('d/m/Y');
    }
}
