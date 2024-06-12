<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $reviewRepository;

    public function test_getAllReviews(): void
    {
        $response = $this->get('api/reviews');

        $response->assertStatus(200);
    }


    public function test_storeReview(): void
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        //create product
        $product = Product::factory()->create();


        // Generate new data for creating the review
        $reviewData = [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 4,
            'comment' => 'Good Product!'
        ];

        // Send a POST request to store the review
        $response = $this->post('api/reviews/products/' . $product->id, $reviewData);

        // Assert that the request was successful (status code 201)
        $response->assertStatus(201);

        // Assert that the review was stored in the database with the provided data
        $this->assertDatabaseHas('reviews', [
            'rating' => $reviewData['rating'],
            'comment' => $reviewData['comment'],
            'product_id' => $reviewData['product_id'],
            'user_id' => $reviewData['user_id']
        ]);
    }

    public function test_findByProductId(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Create a product
        $product = Product::factory()->create();

        //create review for the product
        Review::factory()->count(3)->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);


        // Send a GET request to the route
        $response = $this->get('api/reviews/products/' . $product->id);

        // Assert that the request was successful
        $response->assertStatus(200);

        $response->assertJsonCount(2, 'data');
    }
}
