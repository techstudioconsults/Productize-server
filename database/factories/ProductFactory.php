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
        $user = User::where('account_type', 'premium')->inRandomOrder()->first();
        return [
            'title' => fake()->text('20'),
            'user_id' => $user->id,
            'price' => fake()->randomNumber(4),
            'product_type' => 'digital_product',
            'status' => 'draft',
            'description' => '<p>lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem lorem </p>',
            'data' => '["https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/3d_collection_showcase-20210110-0001.jpg"]',
            'cover_photos' => '["https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/3d_collection_showcase-20210110-0001.jpg"]',
            'thumbnail' => 'https://productize.nyc3.cdn.digitaloceanspaces.com/products-thumbnail/3d_collection_showcase-20210110-0001.jpg',
            'highlights' => '["k", "ki", "kin", "king", "kings", "kingsl", "kingsle", "kingsley", "kingsley", "kingsley"]',
            'tags' => '["Design/painting", "Design"]'
        ];
    }
}
