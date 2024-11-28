<?php

namespace Tests\Feature;

use App\Exceptions\UnAuthorizedException;
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

    public function test_get_all_reviews(): void
    {
        $response = $this->get('api/reviews');

        $response->assertStatus(200);
    }

    public function test_store_review(): void
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
            'comment' => 'Good Product!',
        ];

        // Send a POST request to store the review
        $response = $this->post('api/reviews/products/'.$product->id, $reviewData);

        // Assert that the request was successful (status code 201)
        $response->assertStatus(201);

        // Assert that the review was stored in the database with the provided data
        $this->assertDatabaseHas('reviews', [
            'rating' => $reviewData['rating'],
            'comment' => $reviewData['comment'],
            'product_id' => $reviewData['product_id'],
            'user_id' => $reviewData['user_id'],
        ]);
    }

    public function test_store_review_without_comment(): void
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
        ];

        // Send a POST request to store the review
        $response = $this->post('api/reviews/products/'.$product->id, $reviewData);

        // Assert that the request was successful (status code 201)
        $response->assertStatus(201);

        // Assert that the review was stored in the database with the provided data
        $this->assertDatabaseHas('reviews', [
            'rating' => $reviewData['rating'],
            'product_id' => $reviewData['product_id'],
            'user_id' => $reviewData['user_id'],
        ]);
    }

    public function test_find_by_product_id(): void
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
        $response = $this->get('api/reviews/products/'.$product->id);

        // Assert that the request was successful
        $response->assertStatus(200);

        $response->assertJsonCount(2, 'data');
    }

    public function test_store_review_unauthenticated()
    {
        $product = Product::factory()->create();

        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()
            ->post('/api/reviews/products/'.$product->id);
    }

    public function test_find_by_product_with_no_reviews()
    {
        $product = Product::factory()->create();

        $response = $this->getJson('/api/reviews/products/'.$product->id);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_duplicate_review_prevention()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Create an initial review
        Review::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $reviewData = [
            'rating' => 3,
            'comment' => 'Trying to add another review',
        ];

        $response = $this->actingAs($user)
            ->postJson("/api/reviews/products/{$product->id}", $reviewData);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'You have already reviewed this product.',
            ]);
    }

    public function test_review_for_non_existent_product()
    {
        $user = User::factory()->create();
        $nonExistentProductId = 1334; // Assuming this ID doesn't exist

        $reviewData = [
            'rating' => 4,
            'comment' => 'Great product!',
        ];

        $response = $this->actingAs($user)
            ->postJson("/api/reviews/products/{$nonExistentProductId}", $reviewData);

        $response->assertStatus(404); // Not Found
    }
}
