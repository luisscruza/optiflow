<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
final class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 5000);
        $itbis = fake()->randomFloat(2, 0, $subtotal * 0.18);
        $isc = fake()->randomFloat(2, 0, $subtotal * 0.10);
        $withheldItbis = fake()->randomFloat(2, 0, $itbis);
        $withheldIsr = fake()->randomFloat(2, 0, $subtotal * 0.10);

        return [
            'workspace_id' => Workspace::factory(),
            'contact_id' => ContactFactory::new()->supplier(),
            'document_number' => fake()->bothify('B01########'),
            'issue_date' => fake()->date(),
            'subtotal_amount' => $subtotal,
            'itbis_amount' => $itbis,
            'isc_amount' => $isc,
            'withheld_itbis_amount' => $withheldItbis,
            'withheld_isr_amount' => $withheldIsr,
            'total_amount' => max(0, $subtotal + $itbis + $isc - $withheldItbis - $withheldIsr),
            'is_informal' => false,
            'status' => ExpenseStatus::Pending,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
