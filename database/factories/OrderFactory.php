<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create()->id,
            'reference_no' => fake()->asciify('********************'),
            'quantity' => fake()->numberBetween(1, 10),
            'total_amount' => fake()->randomFloat(2, 10, 100),
            'product_id' => Product::factory()->create(['user_id' => User::factory()->create()]),
        ];
    }
}
