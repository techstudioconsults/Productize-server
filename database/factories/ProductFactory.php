<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->text('20'),
            'price' => fake()->randomNumber(4),
            'product_type' => 'digital_product',
            'status' => 'draft',
            'description' => '<p>lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem </p>',
            'data' => [
                'url' => "https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/3d_collection_showcase-20210110-0001.jpg",
                'format' => 'PDF',
                'size' => 0.35
            ],
            'cover_photos' => ["https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/insidious-emoticon-puzzled-face-isolated-260nw-1939421848(1).png"],
            'thumbnail' => 'https://productize.nyc3.cdn.digitaloceanspaces.com/products-thumbnail/3d_collection_showcase-20210110-0001.jpg',
            'highlights' => ["arrow", "oliver", "queen"],
            'tags' => ["Audio", "Business/Finance", "3D"]
        ];
    }

    // public function configure(): static
    // {
    //     return $this->afterCreating(function (Product $product) {
    //         Customer::factory(10)->create(['product_id' => $product->id]);
    //     });
    // }
}
