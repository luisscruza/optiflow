<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuotationStatus;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\DocumentSubtype;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quotation>
 */
final class QuotationFactory extends Factory
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
            'status' => fake()->randomElement(QuotationStatus::cases()),
            'document_number' => fake()->unique()->numerify('Q-#####'),
            'issue_date' => fake()->date(),
            'due_date' => null,
            'total_amount' => fake()->randomFloat(2, 0, 5000),
            'tax_amount' => fake()->randomFloat(2, 0, 500),
            'discount_amount' => fake()->randomFloat(2, 0, 500),
            'subtotal_amount' => fake()->randomFloat(2, 0, 5000),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'currency_id' => Currency::factory(),
        ];
    }
}
