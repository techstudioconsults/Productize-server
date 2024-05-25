<?php

namespace Tests\Unit\v1\repository;

use App\Exceptions\ApiException;
use App\Exceptions\ModelCastException;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Repositories\CartRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class CartRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CartRepository $cartRepository;

    public function setUp(): void
    {
        parent::setUp();

        // Create an instance of the repository
        $this->cartRepository = new CartRepository();
    }

    public function test_create(): void
    {
        // user_id, product_slug, quantity

        $user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $expected_result = [
            'user_id' => $user->id,
            'product_slug' => $product->slug,
            'quantity' => 3
        ];

        $result = $this->cartRepository->create($expected_result);

        // Assert
        $this->assertInstanceOf(Cart::class, $result);

        $this->assertEquals($expected_result['user_id'], $result->user_id);
        $this->assertEquals($expected_result['product_slug'], $result->product_slug);
        $this->assertEquals($expected_result['quantity'], $result->quantity);
    }

    public function test_query_with_date_filters_and_user(): void
    {
        $user = User::factory()->create();

        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 2, 1, 0);

        // Create test data
        Cart::factory()->create([
            'created_at' => $start_date,
            'user_id' => $user->id,
        ]);

        Cart::factory()->create([
            'created_at' => '2024-03-01',
            'user_id' => $user->id
        ]);

        Cart::factory()->create([
            'created_at' => '2024-02-01',
            'user_id' => User::factory()->create()->id
        ]);

        // Test with date filter
        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'user_id' => $user->id
        ];

        $query = $this->cartRepository->query($filter);

        $results = $query->get();

        // Assert the results
        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->user_id);
        $this->assertEquals($start_date->format('Y-m-d'), $results->first()->created_at->format('Y-m-d'));
    }

    public function test_query_without_date_filters(): void
    {
        $user = User::factory()->create();

        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);

        // Create test data
        Cart::factory()->create([
            'created_at' => $start_date,
            'user_id' => $user->id,
        ]);

        Cart::factory()->create([
            'created_at' => '2024-03-01',
            'user_id' => $user->id
        ]);

        Cart::factory()->create([
            'created_at' => '2024-02-01',
            'user_id' => User::factory()->create()->id
        ]);

        // Test without date filter
        $filter = ['user_id' => $user->id];

        $query = $this->cartRepository->query($filter);

        $results = $query->get();

        // Assert the results
        $this->assertCount(2, $results);
        $this->assertEquals($user->id, $results->first()->user_id);
    }

    public function test_queryRelation_with_empty_filter_returns_original_relation(): void
    {
        // Arrange: Create a user with carts
        $user = User::factory()->hasCarts(3)->create();
        $relation = $user->carts();

        // Act: Call queryRelation with an empty filter
        $result = $this->cartRepository->queryRelation($relation, []);

        // Assert: The result should be the original relation with the correct number of carts
        $this->assertInstanceOf(HasMany::class, $result);
        $this->assertCount(3, $result->get());
    }

    public function test_queryRelation_with_valid_filter_returns_filtered_relation(): void
    {
        // Arrange: Create a user with carts
        $user = User::factory()->create();
        Cart::factory()->create(['user_id' => $user->id, 'quantity' => 1]);
        Cart::factory()->create(['user_id' => $user->id, 'quantity' => 2]);

        $relation = $user->carts();

        // Act: Call queryRelation with a filter for quantity
        $filter = ['quantity' => 1];
        $result = $this->cartRepository->queryRelation($relation, $filter);

        // Assert: The result should be a filtered relation with the correct cart
        $this->assertInstanceOf(HasMany::class, $result);

        $carts = $result->get();

        $this->assertCount(1, $carts);
        $this->assertEquals(1, $carts->first()->quantity);
    }

    public function test_queryRelation_with_date_filter_applies_date_filter(): void
    {
        // Arrange: Create a user with carts
        $user = User::factory()->create();
        $cart1 = Cart::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(10)]);
        $cart2 = Cart::factory()->create(['user_id' => $user->id, 'created_at' => now()]);
        $relation = $user->carts();

        // Act: Call queryRelation with a date filter
        $filter = ['start_date' => now()->subDays(15)->toDateString(), 'end_date' => now()->subDays(5)->toDateString()];
        $result = $this->cartRepository->queryRelation($relation, $filter);

        // Assert: The result should be a filtered relation with the correct cart
        $this->assertInstanceOf(HasMany::class, $result);
        $carts = $result->get();
        $this->assertCount(1, $carts);
        $this->assertEquals($cart1->id, $carts->first()->id);
    }

    public function test_queryRelation_with_invalid_date_filter_throws_exception(): void
    {
        // Arrange: Create a user with carts
        $user = User::factory()->create();
        Cart::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(10)]);
        $relation = $user->carts();

        // Act: Call queryRelation with an invalid date filter
        $filter = ['start_date' => 'invalid-date', 'end_date' => now()->toDateString()];

        // Expect an exception to be thrown
        $this->expectException(ApiException::class);

        $this->cartRepository->queryRelation($relation, $filter);
    }

    public function test_find_with_filters(): void
    {
        $user = User::factory()->create();

        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 2, 1, 0);

        // Create test data
        Cart::factory()->create([
            'created_at' => $start_date,
            'user_id' => $user->id,
        ]);

        Cart::factory()->create([
            'created_at' => '2024-03-01',
            'user_id' => $user->id
        ]);

        Cart::factory()->create([
            'created_at' => '2024-02-01',
            'user_id' => User::factory()->create()->id
        ]);

        // Test with date filter
        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'user_id' => $user->id
        ];

        $results = $this->cartRepository->find($filter);

        // Assert the results
        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->user_id);
        $this->assertEquals($start_date->format('Y-m-d'), $results->first()->created_at->format('Y-m-d'));
    }

    public function test_find_without_filters_should_return_all(): void
    {
        $count = 10;

        Cart::factory()->count($count)->create();

        $results = $this->cartRepository->find();

        // Assert the results
        $this->assertCount($count, $results);
    }

    public function test_find_with_wrong_user_id_returns_empty_array(): void
    {
        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 2, 1, 0);

        $user = User::factory()->create();

        // Arrange: Create a cart with a specific user ID
        Cart::factory()->create([
            'created_at' => $start_date,
            'user_id' => $user->id,
        ]);

        // Act: Attempt to find carts with a non-existing user ID
        $filter = [
            'user_id' => 999,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]; // Assuming 999 is a non-existing user ID

        $result = $this->cartRepository->find($filter);

        // Assert: The result should be empty
        $this->assertEmpty($result, 'Expected no carts to be found for the given user ID');
    }


    public function test_findbyid(): void
    {
        $cart = Cart::factory()->create([
            'user_id' => User::factory()->create()
        ]);

        $result = $this->cartRepository->findById($cart->id);

        assertEquals($cart->id, $result->id);
        $this->assertInstanceOf(Cart::class, $result);
    }

    public function test_findbyid_wrong_id_return_null(): void
    {
        $cart_id = "invalid_cart_id";

        $result = $this->cartRepository->findById($cart_id);

        assertEquals(null, $result);
    }

    public function test_findone_with_slug(): void
    {
        $product = Product::factory()->create(['user_id' => User::factory()->create()->id]);

        Cart::factory()->count(3)->create([
            'product_slug' => $product->slug
        ]);

        $result = $this->cartRepository->findOne(['product_slug' => $product->slug]);

        $this->assertInstanceOf(Cart::class, $result);
        assertEquals($product->slug, $result->product_slug);
    }

    public function test_findone_with_wrong_slug_return_null(): void
    {
        $product = Product::factory()->create(['user_id' => User::factory()->create()->id]);

        Cart::factory()->count(3)->create([
            'product_slug' => $product->slug
        ]);

        $result = $this->cartRepository->findOne(['product_slug' => "wrong_slug"]);

        assertEquals(null, $result);
    }

    public function test_update_cart_successfully(): void
    {
        // Create a cart instance for testing
        $cart = Cart::factory()->create();

        // Define updates for the cart
        $updates = [
            'quantity' => 5,
            'product_slug' => 'updated-product-slug'
        ];

        // Update the cart
        $updatedCart = $this->cartRepository->update($cart, $updates);

        // Assert the cart was updated successfully
        $this->assertEquals($cart->id, $updatedCart->id);
        $this->assertEquals($updates['quantity'], $updatedCart->quantity);
        $this->assertEquals($updates['product_slug'], $updatedCart->product_slug);
    }

    /** @test */
    public function test_update_with_non_cart_model_throws_model_cast_exception(): void
    {
        // Create a user instance for testing
        $user = User::factory()->create();

        // Define updates for the user (not relevant to Cart model)
        $updates = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];

        // Expect ModelCastException when trying to update a non-Cart model
        $this->expectException(ModelCastException::class);

        // Attempt to update a user instance using the cart repository (should throw exception)
        $this->cartRepository->update($user, $updates);
    }
}
