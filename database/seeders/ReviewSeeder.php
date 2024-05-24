<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 22-05-2024
 */

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch some products and users
        $products = Product::inRandomOrder()->take(10)->get();
        $users = User::inRandomOrder()->take(20)->get();

        foreach ($products as $product) {
            $numReviews = random_int(3, 10);

            for ($i = 0; $i < $numReviews; $i++) {
                $user = $users->random();
                $rating = random_int(1, 5);
                $comment = fake()->paragraph();

                Review::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'rating' => $rating,
                    'comment' => $comment,
                ]);
            }
        }
    }
}
