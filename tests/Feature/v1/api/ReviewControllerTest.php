<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Repositories\ReviewRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReviewRepositoryTest extends TestCase
{

    use RefreshDatabase, WithFaker;

    protected ReviewRepository $reviewRepository;

    // /**
    //  * A basic feature test example.
    //  */
    // public function test_example(): void
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }

    public function test_create_review()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $reviewData = [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating'=> 4,
            'comment' => $this->faker->sentence,
        ];

        $review = $this->reviewRepository->create($reviewData);

        $this->assertInstanceOf(Review::class, $review);
        $this->assertDatabaseHas('reviews', $reviewData);
        $this->assertTrue($review->user()->exists());
        $this->assertTrue($review->product()->exists());

    }

}
