<?php

namespace Database\Factories;

use App\Models\User;
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

        // Create a User instance
        $user = User::factory()->create();

        return [
            'title' => fake()->text('20'),
            'price' => fake()->randomNumber(4),
            'product_type' => 'digital_product',
            'status' => 'draft',
            'description' => '<p>lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem </p>',
            'cover_photos' => ['https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/insidious-emoticon-puzzled-face-isolated-260nw-1939421848(1).png'],
            'thumbnail' => 'https://productize.nyc3.cdn.digitaloceanspaces.com/products-thumbnail/3d_collection_showcase-20210110-0001.jpg',
            'highlights' => ['arrow', 'oliver', 'queen'],
            'tags' => ['Audio', 'Business/Finance', '3D'],
            'user_id' => $user->id,
        ];
    }
}
