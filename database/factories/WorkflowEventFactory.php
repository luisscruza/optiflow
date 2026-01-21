<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkflowEvent>
 */
final class WorkflowEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workflow_job_id' => WorkflowJobFactory::new(),
            'from_stage_id' => WorkflowStageFactory::new(),
            'to_stage_id' => WorkflowStageFactory::new(),
            'event_type' => fake()->randomElement(EventType::cases()),
            'user_id' => User::factory(),
            'metadata' => null,
        ];
    }
}
