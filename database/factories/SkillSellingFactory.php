<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SkillSelling>
 */
class SkillSellingFactory extends Factory
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

        // Create a Product instance and associate it with the User
        $product = Product::factory()->create([
            'user_id' => $user->id,
        ]);

        return [
            'level' => 'high',
            'availability' => 'yes',
            'category' => 'Product',
            'link' => 'www.github.com',
            'product_id' => $product->id,
        ];
    }
}
