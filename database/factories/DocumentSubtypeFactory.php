<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentSubtype>
 */
final class DocumentSubtypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Invoice', 'Quotation', 'Credit Note', 'Proforma']),
            'sequence' => '1',
        ];
    }

    /**
     * Create an invoice subtype.
     */
    public function invoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Invoice',
            'sequence' => '1',
        ]);
    }

    /**
     * Create a quotation subtype.
     */
    public function quotation(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Quotation',
            'sequence' => '1',
        ]);
    }

    /**
     * Create a credit note subtype.
     */
    public function creditNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Credit Note',
            'sequence' => '1',
        ]);
    }

    /**
     * Create a proforma subtype.
     */
    public function proforma(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Proforma',
            'sequence' => '1',
        ]);
    }

    /**
     * Create subtype with specific sequence.
     */
    public function withSequence(string $sequence): static
    {
        return $this->state(fn (array $attributes) => [
            'sequence' => $sequence,
        ]);
    }
}
