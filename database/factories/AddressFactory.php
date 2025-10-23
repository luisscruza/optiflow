<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
final class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'type' => fake()->randomElement(['billing', 'shipping', 'both']),
            'province' => fake()->state(),
            'municipality' => fake()->city(),
            'country' => fake()->country(),
            'description' => fake()->streetAddress(),
            'is_primary' => false,
        ];
    }

    /**
     * Indicate that the address is primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that the address is a billing address.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'billing',
        ]);
    }

    /**
     * Indicate that the address is a shipping address.
     */
    public function shipping(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'shipping',
        ]);
    }

    /**
     * Create an address for a specific contact.
     */
    public function forContact(Contact|int $contact): static
    {
        $contactId = $contact instanceof Contact ? $contact->id : $contact;

        return $this->state(fn (array $attributes): array => [
            'contact_id' => $contactId,
        ]);
    }
}
