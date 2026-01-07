<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payment;
use App\Models\WithholdingType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentWithholding>
 */
final class PaymentWithholdingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseAmount = fake()->randomFloat(2, 1000, 50000);
        $percentage = fake()->randomFloat(2, 5, 30);
        $amount = round($baseAmount * ($percentage / 100), 2);

        return [
            'payment_id' => Payment::factory(),
            'withholding_type_id' => WithholdingType::factory(),
            'base_amount' => $baseAmount,
            'percentage' => $percentage,
            'amount' => $amount,
        ];
    }

    /**
     * Associate with a specific payment.
     */
    public function forPayment(Payment $payment): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * Associate with a specific withholding type.
     */
    public function forWithholdingType(WithholdingType $withholdingType): static
    {
        return $this->state(function (array $attributes) use ($withholdingType): array {
            $amount = round($attributes['base_amount'] * ($withholdingType->percentage / 100), 2);

            return [
                'withholding_type_id' => $withholdingType->id,
                'percentage' => $withholdingType->percentage,
                'amount' => $amount,
            ];
        });
    }

    /**
     * Set a specific base amount.
     */
    public function withBaseAmount(float $baseAmount): static
    {
        return $this->state(function (array $attributes) use ($baseAmount): array {
            $amount = round($baseAmount * ($attributes['percentage'] / 100), 2);

            return [
                'base_amount' => $baseAmount,
                'amount' => $amount,
            ];
        });
    }
}
