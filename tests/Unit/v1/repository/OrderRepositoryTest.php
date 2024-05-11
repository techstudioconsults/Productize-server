<?php

namespace Tests\Unit\v1\repository;

use App\Exceptions\UnprocessableException;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\OrderRepository;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private OrderRepository $orderRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = app(OrderRepository::class);
    }

    public function test_create()
    {
        // Arrange
        $user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $expected_result = [
            'user_id' => $user->id,
            'reference_no' => 'ABC123', // Set a sample reference number
            'product_id' => $product->id,
            'quantity' => 1, // Set a sample quantity
            'total_amount' => 100.00 // Set a sample total amount
        ];

        $result = $this->orderRepository->create($expected_result);

        // Assert
        $this->assertInstanceOf(Order::class, $result);

        $this->assertEquals($expected_result['reference_no'], $result->reference_no);
        $this->assertEquals($expected_result['product_id'], $result->product_id);
        $this->assertEquals($expected_result['quantity'], $result->quantity);
        $this->assertEquals($expected_result['total_amount'], $result->total_amount);
    }

    public function test_find_orders_without_date_range()
    {
        // Arrange
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['user_id' => $user->id, 'title' => 'Product 1']);
        $product2 = Product::factory()->create(['user_id' => $user->id, 'title' => 'Product 2']);

        Order::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);
        Order::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id]);

        // Act
        $result = $this->orderRepository->find($user);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertCount(2, $result->get());
    }

    public function test_find_orders_with_date_range()
    {
        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 3, 20, 0);

        $expected_result = 3;

        // Arrange
        $user = User::factory()->create();

        // Create orders within the date range
        Order::factory()->count($expected_result)->state([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
        ])->create([
            'created_at' => Carbon::create(2024, 3, 15, 0),
        ]);

        // Create an order outside the date range
        Order::factory()->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        // Act
        $result = $this->orderRepository->find($user, null, $start_date, $end_date);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertCount($expected_result, $result->get());
    }

    public function test_find_orders_by_product_title()
    {
        // Arrange
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['user_id' => $user->id, 'title' => 'Product 1']);
        $product2 = Product::factory()->create(['user_id' => $user->id, 'title' => 'Product 2']);

        Order::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);
        Order::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id]);

        // Act
        $orders = $this->orderRepository->find($user, 'Product 1');

        // Assert
        $this->assertCount(1, $orders->get());
        $this->assertEquals($product1->id, $orders->first()->product_id);
    }

    public function test_find_orders_with_invalid_date_range_should_throw_unprocceable_exception()
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        $this->expectException(UnprocessableException::class);
        
        $this->orderRepository->find($user, null, 'invalid_date', 'invalid_date');
    }
}
