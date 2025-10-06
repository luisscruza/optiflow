<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Currency;
use App\Models\DocumentSubtype;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
final class InvoiceFactory extends Factory
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
            'contact_id' => Contact::factory(),
            'document_subtype_id' => DocumentSubtype::factory(),
            'status' => 'draft',
            'document_number' => fake()->unique()->numerify('B01########'),
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'total_amount' => fake()->randomFloat(2, 100, 10000),
            'subtotal_amount' => fake()->randomFloat(2, 100, 10000),
            'tax_amount' => fake()->randomFloat(2, 0, 1000),
            'discount_amount' => fake()->randomFloat(2, 0, 500),
            'notes' => fake()->optional()->sentence(),
            'currency_id' => Currency::factory(),
            'payment_term' => 'manual',
        ];
    }
}
