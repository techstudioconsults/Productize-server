<?php

namespace Tests\Unit\v1\repository;

use App\Exceptions\UnprocessableException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
            'total_amount' => 100.00, // Set a sample total amount
        ];

        $result = $this->orderRepository->create($expected_result);

        // Assert
        $this->assertInstanceOf(Order::class, $result);

        $this->assertEquals($expected_result['reference_no'], $result->reference_no);
        $this->assertEquals($expected_result['product_id'], $result->product_id);
        $this->assertEquals($expected_result['quantity'], $result->quantity);
        $this->assertEquals($expected_result['total_amount'], $result->total_amount);
    }

    public function test_find_orders_without_filter()
    {
        $count = 10;

        Order::factory()->count($count)->create();

        // Act
        $result = $this->orderRepository->find();

        // Assert
        $this->assertNotEmpty($result);
        $this->assertCount($count, $result);
        $this->assertInstanceOf(Order::class, $result->first());
    }

    public function test_find_orders_with_filters_date_range()
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

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        // Act
        $result = $this->orderRepository->find($filter);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertCount($expected_result, $result);
    }

    public function test_findbyid(): void
    {
        $expected_result = Order::factory()->create([
            'product_id' => Product::factory()->create(['user_id' => User::factory()->create()->id])->id,
        ]);

        $result = $this->orderRepository->findById($expected_result->id);

        $this->assertEquals($expected_result->toArray(), $result->toArray());
    }

    public function test_findbyid_return_null_for_when_not_found(): void
    {
        $result = $this->orderRepository->findById('id_does_not_exist');

        $this->assertNull($result);
    }

    public function test_findone(): void
    {
        $expected_result = Order::factory()->create([
            'product_id' => Product::factory()->create(['user_id' => User::factory()->create()->id])->id,
        ]);

        $result = $this->orderRepository->findOne(['reference_no' => $expected_result->reference_no]);

        $this->assertEquals($expected_result->toArray(), $result->toArray());
    }

    public function test_findone_return_null_when_not_found(): void
    {
        $result = $this->orderRepository->findOne(['reference_no' => '12345']);

        $this->assertNull($result);
    }

    public function test_query_with_empty_filter_returns_all_orders(): void
    {
        $orders = Order::factory()->count(3)->create();

        $result = $this->orderRepository->query([])->get();

        $this->assertCount(3, $result);
        $this->assertEquals($orders->pluck('id')->sort()->values(), $result->pluck('id')->sort()->values());
    }

    public function test_query_with_date_filter_applies_date_range(): void
    {
        $orders = Order::factory()->count(3)->create();

        $start_date = $orders->first()->created_at->subDay()->toDateString();
        $end_date = $orders->last()->created_at->addDay()->toDateString();

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $result = $this->orderRepository->query($filter)->get();

        $this->assertCount(3, $result);
        $this->assertEquals($orders->pluck('id')->sort()->values(), $result->pluck('id')->sort()->values());
    }

    public function test_query_with_invalid_date_filter_throws_exception(): void
    {
        $this->expectException(UnprocessableException::class);

        $filter = [
            'start_date' => 'invalid_date',
            'end_date' => '2024-12-31',
        ];

        $this->orderRepository->query($filter);
    }

    public function test_query_with_product_title_filter_applies_whereHas(): void
    {
        $product_title = 'Sample Product';

        $user = User::factory()->create();

        $product = Product::factory()->create(['title' => $product_title, 'user_id' => $user->id]);
        $orders = Order::factory()->count(2)->create(['product_id' => $product->id]);

        $filter = [
            'product_title' => $product_title,
        ];

        $result = $this->orderRepository->query($filter)->get();

        $this->assertCount(2, $result);
        $this->assertEquals($orders->pluck('id')->sort()->values(), $result->pluck('id')->sort()->values());
    }

    public function test_query_with_all_filters_applies_correctly(): void
    {
        $product_title = 'Sample Product';

        $user = User::factory()->create();

        $product = Product::factory()->create(['title' => $product_title, 'user_id' => $user->id]);
        $orders = Order::factory()->count(2)->create(['product_id' => $product->id]);

        $start_date = $orders->first()->created_at->subDay()->toDateString();
        $end_date = $orders->last()->created_at->addDay()->toDateString();

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'product_title' => $product_title,
        ];

        $result = $this->orderRepository->query($filter)->get();

        $this->assertCount(2, $result);
        $this->assertEquals($orders->pluck('id')->sort()->values(), $result->pluck('id')->sort()->values());
    }

    public function test_queryrelation_with_empty_filter_returns_original_relation(): void
    {
        $user = User::factory()->create();
        $relation = $user->orders();

        $result = $this->orderRepository->queryRelation($relation, []);

        $this->assertInstanceOf(Relation::class, $result);
        $this->assertEquals($relation, $result);
    }

    public function test_queryrelation_with_date_filter(): void
    {
        $user = User::factory()->create();
        $relation = $user->orders();

        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 3, 20, 0);

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $result = $this->orderRepository->queryRelation($relation, $filter);

        $this->assertInstanceOf(Relation::class, $result);

        // Check if whereBetween was applied correctly
        $sql = $result->toSql();
        $this->assertStringContainsString('between', $sql);
        $this->assertStringContainsString('`orders`.`created_at`', $sql); // Include backticks

        // Check if bindings contain the correct dates
        $bindings = $result->getBindings();

        $this->assertEquals($start_date->format('Y-m-d H:i:s'), $bindings[1]);
        $this->assertEquals($end_date->format('Y-m-d H:i:s'), $bindings[2]);
    }

    public function test_queryrelation_with_invalid_date_filter_throws_exception(): void
    {
        $this->expectException(UnprocessableException::class);

        $user = User::factory()->create();
        $relation = $user->orders();

        $filter = [
            'start_date' => 'invalid_date',
            'end_date' => '2024-12-31',
        ];

        $this->orderRepository->queryRelation($relation, $filter);
    }

    public function test_queryrelation_with_product_title_filter_applies_whereHas(): void
    {
        $product_title = 'Sample Product';

        $user = User::factory()->create();

        $expected_result = Order::factory()->create([
            'product_id' => Product::factory()->create([
                'user_id' => $user->id,
                'title' => $product_title,
            ])->id,
        ]);

        $relation = $user->orders();

        $filter = [
            'product_title' => $product_title,
        ];

        $result = $this->orderRepository->queryRelation($relation, $filter);

        $this->assertInstanceOf(Relation::class, $result);

        $sql = $result->toSql();

        // Check if whereHas was applied correctly
        $this->assertStringContainsString('exists', $sql);
        $this->assertStringContainsString('product', $sql);
        $this->assertStringContainsString('title', $sql);

        $this->assertNotNull($result->get());

        $actualResult = $result->get()->first()->toArray();

        // Cast the values to match the types in the expected array
        $actualResult['quantity'] = (int) $actualResult['quantity'];
        $actualResult['total_amount'] = (float) $actualResult['total_amount'];

        unset($actualResult['laravel_through_key']);

        $this->assertEquals($expected_result->toArray(), $actualResult);
    }

    public function test_queryRelation_with_all_filters_applies_correctly(): void
    {
        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 3, 20, 0);
        $product_title = 'Sample Product';

        $user = User::factory()->create();
        $relation = $user->orders();

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'product_title' => $product_title,
        ];

        $result = $this->orderRepository->queryRelation($relation, $filter);

        $this->assertInstanceOf(Relation::class, $result);

        $sql = $result->toSql();

        // Check if whereBetween was applied correctly
        $this->assertStringContainsString('between', $sql);
        $this->assertStringContainsString('`orders`.`created_at`', $sql);

        // Check if whereHas was applied correctly
        $this->assertStringContainsString('exists', $sql);
        $this->assertStringContainsString('product', $sql);
        $this->assertStringContainsString('title', $sql);
    }
}
