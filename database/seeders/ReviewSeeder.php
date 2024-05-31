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
use Database\Seeders\Traits\DisableForeignKeys;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    use TruncateTable, DisableForeignKeys;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->disableForeignKeys();
        $this->truncate('reviews');


        // Fetch some products and users
        $user = User::factory()->create();
        $products = Product::factory()->count(10)->create([
            'user_id'=> $user->id
        ]);
        $users = User::factory()->count(10)->create();
        foreach ($products as $product) {
            $numReviews = random_int(3, 10);

            for ($i = 0; $i < $numReviews; $i++) {
                $user = $users->random();
                $rating = random_int(1, 5);
                $comment = fake()->paragraph();

                Review::factory()->create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'rating' => $rating,
                    'comment' => $comment,
                ]);
            }
        }

        $this->enableForeignKeys();
    }
}