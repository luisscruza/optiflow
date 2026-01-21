<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhatsappAccount>
 */
final class WhatsappAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'phone_number_id' => fake()->numerify('##########'),
            'business_account_id' => fake()->optional()->numerify('##########'),
            'access_token' => fake()->sha1(),
            'display_phone_number' => fake()->optional()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
