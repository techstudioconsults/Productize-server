<?php

namespace Tests\Unit\v1\repository;

use App\Exceptions\ApiException;
use App\Exceptions\UnprocessableException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\CustomerRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CustomerRepository $customerRepository;

    private UserRepository $userRepository;

    private ProductRepository $productRepository;

    protected $full_name;

    protected $email;

    protected $password;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = app(CustomerRepository::class);
        $this->userRepository = app(UserRepository::class);
        $this->productRepository = app(ProductRepository::class);

        $this->full_name = 'Tobi Olanitori';
        $this->email = 'tobiolanitori@gmail.com';
        $this->password = 'password123';
    }

    public function test_create_customer()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        // Act
        $customer = $this->customerRepository->create([
            'user_id' => $order->user->id,
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
        ]);

        // Assert
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals($order->user->id, $customer->user_id);
        $this->assertEquals($order->product->user->id, $customer->merchant_id);
        $this->assertEquals($order->id, $customer->order_id);
    }

    public function test_query_method_with_date_range_and_user()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 2, 1, 0);

        // Create two customers associated with the user within date range
        Customer::factory()->count(2)->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
            'created_at' => $start_date,
        ]);

        // Create two customers associated with the user outside the date range
        Customer::factory()->count(2)->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
            'created_at' => '2024-03-01',
        ]);

        // Test with date filter
        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'merchant_id' => $user->id,
        ];

        $query = $this->customerRepository->query($filter);

        $results = $query->get();

        // Assert the results
        $this->assertCount(2, $results);
        $this->assertEquals($user->id, $results->first()->merchant_id);
        $this->assertEquals($start_date->format('Y-m-d'), $results->first()->created_at->format('Y-m-d'));
    }

    public function test_query_method_without_date_range(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        Customer::factory()->count(5)->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
        ]);

        // Test without date filter
        $filter = [
            'merchant_id' => $user->id,
        ];

        $query = $this->customerRepository->query($filter);

        $results = $query->get();

        // Assert the results
        $this->assertCount(5, $results);
    }

    public function test_query_relation_with_valid_filter_returns_filtered_relation(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 2, 1, 0);

        // Create two customers associated with the user within date range
        Customer::factory()->count(2)->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
            'created_at' => $start_date,
        ]);

        // Create two customers associated with the user outside the date range
        Customer::factory()->count(2)->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
            'created_at' => '2024-03-01',
        ]);

        $relation = $user->customers();

        // Act: Call queryRelation with a filter for quantity
        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'merchant_id' => $user->id,
        ];

        $result = $this->customerRepository->queryRelation($relation, $filter);

        // Assert: The result should be a filtered relation with the correct cart
        $this->assertInstanceOf(HasMany::class, $result);

        $customers = $result->get();

        $this->assertCount(2, $customers);
        $this->assertEquals($user->id, $customers->first()->merchant_id);
    }

    public function test_query_relation_with_invalid_date_filter_throws_exception(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        Customer::factory()->count(2)->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
        ]);

        // Act: Call queryRelation with an invalid date filter
        $filter = ['start_date' => 'invalid-date', 'end_date' => now()->toDateString()];

        // Expect an exception to be thrown
        $this->expectException(ApiException::class);

        $relation = $user->customers();

        $this->customerRepository->queryRelation($relation, $filter);
    }

    public function test_find_customers_without_filter()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        // Create two customers associated with the user
        Customer::factory()->count(5)->create([
            'user_id' => $user->id,
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
        ]);

        // Assert
        $result = $this->customerRepository->find();

        $this->assertNotEmpty($result); // Ensure the result is not empty
        $this->assertCount(5, $result);
    }

    public function test_find_customer_with_filter()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 2, 1, 0);

        // Create two customers associated with the user within date range
        Customer::factory()->count(2)->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
            'created_at' => $start_date,
        ]);

        // Create two customers associated with the user outside the date range
        Customer::factory()->count(2)->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
            'created_at' => '2024-03-01',
        ]);

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'merchant_id' => $user->id,
        ];

        // Act
        $result = $this->customerRepository->find($filter);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals($user->id, $result->first()->merchant_id);
    }

    public function test_find_customers_by_invalid_date_range_should_throw_unprocessable_exeception()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        Customer::factory()->count(2)->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
        ]);

        $start_date = 'invalid_date';
        $end_date = 'invalid_date';

        // Assert that the expected exception is thrown
        $this->expectException(UnprocessableException::class);

        $filter = ['start_date' => $start_date, 'end_date' => $end_date];

        $this->customerRepository->find($filter);
    }

    public function test_find_without_filters(): void
    {
        $this->customerRepository->seed();

        $result = $this->customerRepository->find();

        $this->assertCount(125, $result);
        $this->assertInstanceOf(Customer::class, $result->first());
    }

    public function test_findbyid_method(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $expected_customer = Customer::factory()->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
        ]);

        $customer = $this->customerRepository->findById($expected_customer->id);

        $this->assertEquals($expected_customer->id, $customer->id);
        $this->assertInstanceOf(Customer::class, $customer);
    }

    public function test_findbyid_wrong_id_return_null(): void
    {
        $customer_id = 'invalid_customer_id';

        $result = $this->customerRepository->findById($customer_id);

        $this->assertEquals(null, $result);
    }

    public function test_findone(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $expected_customer = Customer::factory()->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
        ]);

        $result = $this->customerRepository->findOne(['merchant_id' => $user->id]);

        $this->assertInstanceOf(Customer::class, $result);
        $this->assertEquals($expected_customer->id, $result->id);
    }

    public function test_findone_with_invalid_filter_return_null(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        Customer::factory()->create([
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
        ]);

        $result = $this->customerRepository->findOne(['merchant_id' => 'wrong id']);

        $this->assertEquals(null, $result);
    }
}
