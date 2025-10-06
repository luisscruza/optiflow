<?php

declare(strict_types=1);

namespace Database\Factories\Central;

use App\Models\Central\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
final class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->uuid(),
            'name' => fake()->company(),
            'domain' => fake()->unique()->domainName(),
            'client_id' => Client::factory(),
        ];
    }
}
