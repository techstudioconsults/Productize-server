<?php

namespace Tests\Unit\v1\repository;

use App\Exceptions\UnprocessableException;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Support\Carbon;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = app(CustomerRepository::class);
        $this->userRepository = app(UserRepository::class);
        $this->productRepository = app(ProductRepository::class);


        $this->full_name = "Tobi Olanitori";
        $this->email = "tobiolanitori@gmail.com";
        $this->password = "password123";
    }


    public function test_Create_Customer()
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

    public function test_find_customers_without_date_range()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        // Create two customers associated with the user
        Customer::factory()->create([
            'user_id' => $user->id,
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id
        ]);

        // Assert
        $result = $this->customerRepository->findByRelation($user);

        $this->assertNotEmpty($result); // Ensure the result is not empty
        $this->assertCount(1, $result->get());
    }

    public function test_find_customer_with_date_range()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 3, 20, 0);

        // Create customers associated with the orders within the date range
        Customer::factory()->create([
            'user_id' => $user->id,
            'merchant_id' => $order->product->user->id,
            'order_id' => $order->id,
            'created_at' => Carbon::create(2024, 3, 19, 0),
        ]);


        // Act
        $result = $this->customerRepository->findByRelation($user, ['start_date' => $start_date, 'end_date' => $end_date]);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertCount(1, $result->get());
    }

    public function test_Find_Customers_By_Invalid_DateRange_should_throw_unprocessable_exeception()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $start_date = 'invalid_date';
        $end_date = 'invalid_date';

        // Assert that the expected exception is thrown
        $this->expectException(UnprocessableException::class);

        // Act & Assert
        $this->customerRepository->findByRelation($user, ['start_date' => $start_date, 'end_date' => $end_date]);
    }
}
