<?php

namespace Tests\Unit;

use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Repositories\ReviewRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ReviewRepository $reviewRepository;

    protected function setUp(): void
    {

        parent::setUp();
        $this->reviewRepository = new ReviewRepository();
    }

    /**
     *  Test the create method
     */
    public function testCreateReview()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'rating' => 4,
            'comment' => 'Great Product',
            'product_id' => $product->id,
            'user_id' => $user->id,
        ];

        $review = $this->reviewRepository->create($data);
        $this->assertInstanceOf(Review::class, $review);
        $this->assertEquals($data['rating'], $review->rating);
        $this->assertEquals($data['comment'], $review->comment);
        $this->assertEquals($data['product_id'], $review->product_id);
        $this->assertEquals($data['user_id'], $review->user_id);
    }

    /**
     * Test the find method.
     */
    public function testFindReviews()
    {
        $count = 10;

        Review::factory()->count($count)->create();

        $reviews = $this->reviewRepository->find();

        $this->assertNotEmpty($reviews);
        $this->assertInstanceOf(Review::class, $reviews->first());
        $this->assertCount(10, $reviews);
    }

    public function testQueryReviewsByProductTitle()
    {
        $product1 = Product::factory()->create(['title' => 'Product 1']);
        $product2 = Product::factory()->create(['title' => 'Product 2']);

        Review::factory()->create([
            'product_id' => $product1->id,
            'comment' => 'Review for Product 1',
        ]);

        Review::factory()->create([
            'product_id' => $product2->id,
            'comment' => 'Review for Product 2',
        ]);

        $filter = ['product_title' => 'Product 1'];
        $reviews = $this->reviewRepository->query($filter)->get();

        $this->assertCount(1, $reviews);
        $this->assertEquals('Review for Product 1', $reviews->first()->comment);
    }

    public function test_update_review_successfully(): void
    {
        // Create a review instance for testing
        $review = Review::factory()->create();

        // Define updates for the review
        $updates = [
            'rating' => 5,
            'comment' => 'Review Updated',
        ];

        // Update the review
        $updatedReview = $this->reviewRepository->update($review, $updates);

        // Assert the review was updated successfully
        $this->assertEquals($review->id, $updatedReview->id);
        $this->assertEquals($updates['rating'], $updatedReview->rating);
        $this->assertEquals($updates['comment'], $updatedReview->comment);
    }

    public function testQueryByProductTitle()
    {
        $product1 = Product::factory()->create(['title' => 'Amazing Product']);
        $product2 = Product::factory()->create(['title' => 'Ordinary Product']);

        Review::factory()->create(['product_id' => $product1->id]);
        Review::factory()->create(['product_id' => $product2->id]);

        $query = $this->reviewRepository->query(['product_title' => 'Amazing']);
        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals($product1->id, $results->first()->product_id);
    }

    public function test_Create_Review_With_Invalid_Rating()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'rating' => 6, // Invalid rating
            'comment' => 'Great Product',
            'product_id' => $product->id,
            'user_id' => $user->id,
        ];

        $this->expectException(BadRequestException::class);
        $this->reviewRepository->create($data);
    }

    public function test_findbyid_return_null_for_when_not_found(): void
    {
        $result = $this->reviewRepository->findById('id_does_not_exist');

        $this->assertNull($result);
    }

    public function test_update_with_non_review_model_throws_model_cast_exception(): void
    {
        // Create a user instance for testing
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Define updates for the review
        $updates = [
            'rating' => 3,
            'comment' => 'Fair product',
            'product_id' => $product->id,
            'user_id' => $user->id,
        ];

        // Expect ModelCastException when trying to update a non-review model
        $this->expectException(ModelCastException::class);

        // Attempt to update a review instance using the user repository (should throw exception)
        $this->reviewRepository->update($user, $updates);
    }

    public function testQueryWithNonExistentProductTitle()
    {
        Review::factory()->count(3)->create();

        $query = $this->reviewRepository->query(['product_title' => 'Non-existent Product']);
        $results = $query->get();

        $this->assertCount(0, $results);
    }

    public function testQueryWithProductTitleAndOtherFilters()
    {
        $product1 = Product::factory()->create(['title' => 'Fancy Product']);
        $product2 = Product::factory()->create(['title' => 'Another Fancy Product']);

        Review::factory()->create(['product_id' => $product1->id, 'rating' => 5]);
        Review::factory()->create(['product_id' => $product2->id, 'rating' => 4]);
        Review::factory()->create(['product_id' => $product2->id, 'rating' => 3]);

        $query = $this->reviewRepository->query(['product_title' => 'Fancy', 'rating' => 4]);
        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals($product2->id, $results->first()->product_id);
    }

    public function testGetAverageRatingForProduct()
    {
        $product = Product::factory()->create();

        // reviews for the product with different ratings
        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 5,
        ]);

        // Calc expected average rating
        $expectedAverageRating = (4 + 3 + 5) / 3;

        // Get the average rating from the repository
        $averageRating = $this->reviewRepository->getAverageRatingForProduct($product);

        // Assert that the average rating is correct
        $this->assertEquals($expectedAverageRating, $averageRating);
    }
}
