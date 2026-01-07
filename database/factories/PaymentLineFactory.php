<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChartAccount;
use App\Models\Payment;
use App\Models\PaymentConcept;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentLine>
 */
final class PaymentLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 10);
        $unitPrice = fake()->randomFloat(2, 100, 10000);
        $subtotal = $quantity * $unitPrice;
        $taxAmount = $subtotal * 0.18; // Default ITBIS 18%
        $total = $subtotal + $taxAmount;

        return [
            'payment_id' => Payment::factory(),
            'chart_account_id' => null,
            'payment_concept_id' => null,
            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_amount' => $taxAmount,
            'tax_id' => null,
            'total' => $total,
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
     * Associate with a chart account.
     */
    public function withChartAccount(?ChartAccount $chartAccount = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'chart_account_id' => $chartAccount?->id ?? ChartAccount::factory()->income(),
        ]);
    }

    /**
     * Associate with a payment concept.
     */
    public function withPaymentConcept(?PaymentConcept $concept = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_concept_id' => $concept?->id ?? PaymentConcept::factory(),
        ]);
    }

    /**
     * Associate with a tax.
     */
    public function withTax(?Tax $tax = null): static
    {
        return $this->state(function (array $attributes) use ($tax): array {
            $taxModel = $tax ?? Tax::query()->first();
            $subtotal = $attributes['quantity'] * $attributes['unit_price'];
            $taxAmount = $taxModel ? $subtotal * ($taxModel->rate / 100) : 0;

            return [
                'tax_id' => $taxModel?->id,
                'tax_amount' => $taxAmount,
                'total' => $subtotal + $taxAmount,
            ];
        });
    }

    /**
     * Create a line without tax.
     */
    public function withoutTax(): static
    {
        return $this->state(function (array $attributes): array {
            $subtotal = $attributes['quantity'] * $attributes['unit_price'];

            return [
                'tax_id' => null,
                'tax_amount' => 0,
                'total' => $subtotal,
            ];
        });
    }
}
