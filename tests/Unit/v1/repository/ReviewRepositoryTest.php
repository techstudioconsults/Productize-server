<?php

namespace Tests\Unit;

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


   protected function setUp():void
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
            'rating'=> 4,
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
   
}
