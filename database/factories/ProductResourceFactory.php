<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductResource>
 */
class ProductResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->word . '.' . $this->faker->fileExtension,
            'url' => $this->faker->url,
            'size' => $this->faker->numberBetween(1000, 10000000), // 1KB to 10MB
            'mime_type' => $this->faker->mimeType,
            'extension' => $this->faker->fileExtension,
        ];
    }
}
