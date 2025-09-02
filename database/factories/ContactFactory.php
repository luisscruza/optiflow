<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
final class ContactFactory extends Factory
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
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'contact_type' => fake()->randomElement(['customer', 'supplier', 'both']),
            'metadata' => [
                'tax_id' => fake()->optional()->regexify('[A-Z]{2}[0-9]{9}'),
                'website' => fake()->optional()->url(),
                'notes' => fake()->optional()->sentence(),
            ],
        ];
    }

    /**
     * Indicate that the contact is a customer.
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'contact_type' => 'customer',
        ]);
    }

    /**
     * Indicate that the contact is a supplier.
     */
    public function supplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'contact_type' => 'supplier',
        ]);
    }

    /**
     * Indicate that the contact is both customer and supplier.
     */
    public function both(): static
    {
        return $this->state(fn (array $attributes) => [
            'contact_type' => 'both',
        ]);
    }

    /**
     * Create a contact for a specific workspace.
     */
    public function forWorkspace(Workspace|int $workspace): static
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspaceId,
        ]);
    }
}
