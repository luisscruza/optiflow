<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductRecipe>
 */
final class ProductRecipeFactory extends Factory
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
            'created_by' => User::factory(),
            'contact_id' => Contact::factory(),
            'optometrist_id' => Contact::factory(),
            'product_id' => MastertableItem::factory()->state(function (): array {
                return [
                    'mastertable_id' => Mastertable::query()->firstOrCreate(
                        ['alias' => 'productos_recetarios'],
                        [
                            'name' => 'Productos recetarios',
                            'description' => 'Productos configurables para recetarios.',
                        ],
                    )->id,
                ];
            }),
            'indication' => fake()->sentence(),
        ];
    }
}
