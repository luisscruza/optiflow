<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\DocumentSubtype;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
final class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['invoice', 'quotation']);
        $issueDate = fake()->dateTimeBetween('-3 months', 'now');
        $dueDate = $type === 'invoice' ? fake()->dateTimeBetween($issueDate, '+30 days') : null;

        return [
            'workspace_id' => Workspace::factory(),
            'contact_id' => Contact::factory(),
            'type' => $type,
            'document_subtype_id' => DocumentSubtype::factory(),
            'status' => fake()->randomElement(['draft', 'pending', 'approved', 'sent', 'paid', 'cancelled']),
            'document_number' => null, // Will be auto-generated
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'total_amount' => 0, // Will be calculated from items
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Create an invoice.
     */
    public function invoice(): static
    {
        return $this->state(function (array $attributes) {
            $issueDate = $attributes['issue_date'] ?? fake()->dateTimeBetween('-3 months', 'now');

            return [
                'type' => 'invoice',
                'document_subtype_id' => DocumentSubtype::factory()->invoice(),
                'due_date' => fake()->dateTimeBetween($issueDate, '+30 days'),
                'status' => fake()->randomElement(['draft', 'sent', 'paid']),
            ];
        });
    }

    /**
     * Create a quotation.
     */
    public function quotation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'quotation',
            'document_subtype_id' => DocumentSubtype::factory()->quotation(),
            'due_date' => null,
            'status' => fake()->randomElement(['draft', 'sent', 'approved', 'expired']),
        ]);
    }

    /**
     * Create a draft document.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Create a sent document.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    /**
     * Create a paid document.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'type' => 'invoice', // Only invoices can be paid
        ]);
    }

    /**
     * Create an overdue document.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'invoice',
            'status' => 'sent',
            'issue_date' => fake()->dateTimeBetween('-6 months', '-2 months'),
            'due_date' => fake()->dateTimeBetween('-2 months', '-1 week'),
        ]);
    }

    /**
     * Create document for specific workspace and contact.
     */
    public function forWorkspaceAndContact(Workspace|int $workspace, Contact|int $contact): static
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;
        $contactId = $contact instanceof Contact ? $contact->id : $contact;

        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspaceId,
            'contact_id' => $contactId,
        ]);
    }

    /**
     * Create document with specific total amount.
     */
    public function withTotal(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => $amount,
        ]);
    }

    /**
     * Create document with specific status.
     */
    public function withStatus(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
