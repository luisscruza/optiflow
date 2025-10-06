<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactType;
use App\Enums\IdentificationType;
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
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'contact_type' => ContactType::cases()[array_rand(ContactType::cases())],
            'phone_primary' => fake()->phoneNumber(),
            'phone_secondary' => fake()->optional()->phoneNumber(),
            'mobile' => fake()->optional()->phoneNumber(),
            'fax' => fake()->optional()->phoneNumber(),
            'identification_type' => IdentificationType::cases()[array_rand(IdentificationType::cases())],
            'identification_number' => fake()->optional()->bothify('?#?#?#?#'),
            'status' => 'active',
            'observations' => fake()->optional()->sentence(),
            'credit_limit' => fake()->randomFloat(2, 0, 10000),
            'metadata' => null,
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
