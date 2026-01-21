<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkflowJob>
 */
final class WorkflowJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'workflow_id' => WorkflowFactory::new(),
            'workflow_stage_id' => WorkflowStageFactory::new(),
            'contact_id' => Contact::factory(),
            'invoice_id' => Invoice::factory(),
            'prescription_id' => PrescriptionFactory::new(),
            'notes' => fake()->optional()->sentence(),
            'metadata' => null,
            'priority' => fake()->randomElement(['low', 'normal', 'high']),
            'due_date' => fake()->optional()->dateTime(),
            'started_at' => null,
            'completed_at' => null,
            'canceled_at' => null,
        ];
    }
}
