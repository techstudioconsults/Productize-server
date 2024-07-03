<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DigitalProduct>
 */
class DigitalProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'product_id' => Product::factory(),
            'category' => $this->faker->randomElement(['EBOOK', 'VIDEO', 'AUDIO']),
        ];
    }
}
