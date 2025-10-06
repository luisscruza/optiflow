<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
final class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_account_id' => BankAccount::factory(),
            'currency_id' => Currency::factory(),
            'invoice_id' => Invoice::factory(),
            'payment_date' => fake()->date(),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'amount' => fake()->randomFloat(2, 10, 10000),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
