<?php

namespace Tests\Unit\v1\repository;

use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnprocessableException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use ReflectionClass;
use Illuminate\Validation\Validator;

use function PHPUnit\Framework\assertEquals;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $userRepository;

    protected $full_name;
    protected $email;
    protected $password;

    public function setUp(): void
    {
        parent::setUp();

        $this->full_name = "Tobi Olanitori";
        $this->email = "tobiolanitori@gmail.com";
        $this->password = "password123";

        $this->userRepository = new UserRepository();
    }

    /**
     * Test Create User
     */
    public function test_create_user(): void
    {
        $credentials = [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'password' => $this->password
        ];

        // Create the user
        $user = $this->userRepository->create($credentials);

        // Assert user is saved in the db
        $this->assertDatabaseHas('users', [
            'email' => $this->email,
        ]);

        // Assert email is correctly saved
        $this->assertEquals($this->email, $user->email);

        // Assert the full name is correctly set
        $this->assertEquals($this->full_name, $user->full_name);

        // Assert an instance of User class is returned
        $this->assertInstanceOf(User::class, $user);

        // Assert the password is correctly set
        $this->assertTrue(Hash::check('password123', $user->password));

        // Assert the user gets a free trial
        $this->assertEquals("free_trial", $user->account_type);
    }

    public function test_create_user_with_no_email_throws_BadRequestException()
    {
        $this->expectException(BadRequestException::class);

        // Attempt to create a user without an email
        $this->userRepository->create([
            'full_name' => $this->full_name,
            'password' => $this->password
        ]);
    }

    public function test_update_user()
    {
        $expected_user = User::factory()->create();

        $user = $this->userRepository->update($expected_user, [
            'full_name' => 'updated',
            'username' => 'updated',
            'phone_number' => '123456',
            'bio' => 'bio',
            'twitter_account' => 'https://account.com',
            'facebook_account' => 'https://account.com',
            'youtube_account' => 'https://account.com',
            'alt_email' => 'alt@email.com',
            'product_creation_notification' => true,
            'purchase_notification' => true,
            'news_and_update_notification' => true,
            'payout_notification' => true
        ]);

        // Assert email is correctly saved
        $this->assertEquals($expected_user->email, $user->email);

        // Assert full name is correctly saved
        $this->assertEquals($user->full_name, 'updated');

        $this->assertEquals($user->username, 'updated');

        // Assert phone number is correctly saved
        $this->assertEquals($user->phone_number, '123456');

        // Assert bio is correctly saved
        $this->assertEquals($user->bio, 'bio');

        // Assert twitter is correctly saved
        $this->assertEquals($user->twitter_account, 'https://account.com');

        $this->assertEquals($user->facebook_account, 'https://account.com');

        $this->assertEquals($user->youtube_account, 'https://account.com');

        $this->assertEquals($user->alt_email, 'alt@email.com');

        $this->assertTrue((bool)$user->product_creation_notification);

        $this->assertTrue((bool)$user->news_and_update_notification);

        $this->assertTrue((bool)$user->payout_notification);
    }

    public function test_update_user_email_should_throw_bad_request_exception()
    {
        $this->expectException(BadRequestException::class);

        $user = User::factory()->create();

        $this->userRepository->update($user, [
            'email' => 'updated@email.com'
        ]);
    }

    public function test_guarded_update()
    {
        $expected_user = User::factory()->create();

        $expected_result = "updated";

        $user =  $this->userRepository->guardedUpdate($expected_user->email, "full_name", $expected_result);

        // Assert name has changed
        assertEquals($user->full_name, $expected_result);

        // Assert a user instance is returned
        $this->assertInstanceOf(User::class, $user);
    }

    public function test_guarded_update_email_should_throw_bad_request()
    {
        $this->expectException(BadRequestException::class);

        $user = User::factory()->create();

        $this->userRepository->guardedUpdate($user->email, "email", "updated@email");
    }

    public function test_guarded_update_column_not_found_should_throw_UnprocessableException()
    {
        $this->expectException(UnprocessableException::class);

        $user = User::factory()->create();

        $this->userRepository->guardedUpdate($user->email, "doesnt_exit", "unprocessable");
    }

    public function test_guarded_update_email_not_found_should_throw_NotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $this->userRepository->guardedUpdate($this->email, "full_name", "not found");
    }

    public function test_get_total_sales_without_date_range()
    {
        // Arrange
        $user = User::factory()->create();

        // Set up the expected total sales
        $expected_result = 3;

        // Seed the db
        Order::factory()->count($expected_result)->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
        ]);

        // Act
        $result = $this->userRepository->getTotalSales($user);

        // Assert
        $this->assertEquals($expected_result, $result);
    }

    public function test_get_total_sales_with_date_range()
    {
        // Arrange
        $user = User::factory()->create();

        // Define the date range
        $startDate = Carbon::create(2024, 1, 1, 0);
        $endDate = Carbon::create(2024, 3, 20, 0);

        // Create orders within the date range
        Order::factory()->count(3)->state([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
        ])->create([
            'created_at' => Carbon::create(2024, 3, 15, 0),
        ]);

        // Create an order outside the date range
        Order::factory()->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        // Set up the expected total sales
        $expectedTotalSales = 3;

        // Act
        $totalSales = $this->userRepository->getTotalSales($user, $startDate, $endDate);

        // Assert
        $this->assertEquals($expectedTotalSales, $totalSales);
    }

    public function test_get_total_sales_with_invalid_date_range_should_throw_UnprocessableException()
    {
        // Arrange
        $user = User::factory()->create();

        // Define an invalid date range
        $startDate = 'invalid-date';
        $endDate = 'invalid-date';

        // Assert that the expected exception is thrown
        $this->expectException(UnprocessableException::class);

        // Act
        $this->userRepository->getTotalSales($user, $startDate, $endDate);
    }

    public function test_get_total_revenues()
    {
        // Arrange
        $user = User::factory()->create();

        $amount1 = 10000;
        $amount2 = 10000;
        $amount3 = 5000;

        // Seed db
        Order::factory()
            ->count(3)
            ->state(new Sequence(
                ['total_amount' => $amount1],
                ['total_amount' => $amount2],
                ['total_amount' => $amount3],
            ))
            ->create([
                'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
                'created_at' => Carbon::create(2024, 3, 15, 0),
            ]);

        $expected_result = $amount1 + $amount2 + $amount3;

        $result = $this->userRepository->getTotalRevenues($user);

        $this->assertEquals($expected_result, $result);
    }

    public function test_get_total_revenue_with_date_range()
    {
        // Arrange
        $user = User::factory()->create();

        $amount1 = 10000;
        $amount2 = 10000;
        $amount3 = 5000;

        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 3, 20, 0);

        // Create orders within the date range
        Order::factory()
            ->count(3)
            ->state(
                new Sequence(
                    ['total_amount' => $amount1],
                    ['total_amount' => $amount2],
                    ['total_amount' => $amount3],
                ),
            )
            ->create([
                'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
                'created_at' => Carbon::create(2024, 3, 15, 0)
            ]);

        // Create an order outside the date range
        Order::factory()->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
            'total_amount' => 80000
        ]);

        $expected_result = $amount1 + $amount2 + $amount3;

        $result = $this->userRepository->getTotalRevenues($user, $start_date, $end_date);

        $this->assertEquals($expected_result, $result);
    }

    public function test_get_revenue_throw_UnprocessableException_for_invalid_date_range()
    {
        // Arrange
        $user = User::factory()->create();

        // Define an invalid date range
        $startDate = 'invalid-date';
        $endDate = 'invalid-date';

        // Assert that the expected exception is thrown
        $this->expectException(UnprocessableException::class);

        // Act
        $this->userRepository->getTotalRevenues($user, $startDate, $endDate);
    }

    public function test_get_total_customers()
    {
        $merchant = User::factory()->create();

        $expected_result = 10;

        // Create 10 products for the merchant
        $products = Product::factory()->count(10)->create(['user_id' => $merchant->id]);

        // Create 10 orders for the merchant with specific product IDs
        $orders = Order::factory()->count(10)->create([
            'user_id' => $merchant->id,
            'product_id' => function () use ($products) {
                return $products->random()->id;
            },
            'created_at' => Carbon::create(2024, 3, 21, 0),
            'total_amount' => 80000,
        ]);

        // Create 10 customers for the merchant with specific order IDs
        Customer::factory()->count($expected_result)->create([
            'merchant_id' => $merchant->id,
            'order_id' => function () use ($orders) {
                return $orders->random()->id;
            },
            'user_id' => function () {
                return User::factory()->create()->id;
            }
        ]);

        $result = $this->userRepository->getTotalCustomers($merchant);

        $this->assertEquals($expected_result, $result);
    }

    public function test_get_total_customers_with_date_range()
    {

        $merchant = User::factory()->create();

        $expected_result = 10;

        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 3, 20, 0);

        // Create 10 products for the merchant
        $products = Product::factory()->count(10)->create(['user_id' => $merchant->id]);

        // Create 10 orders for the merchant with specific product IDs
        $orders = Order::factory()->count(10)->create([
            'user_id' => $merchant->id,
            'product_id' => function () use ($products) {
                return $products->random()->id;
            },
            'created_at' => Carbon::create(2024, 3, 15, 0),
            'total_amount' => 80000,
        ]);

        // Create 10 customers for the merchant with specific order IDs
        Customer::factory()->count($expected_result)->create([
            'merchant_id' => $merchant->id,
            'order_id' => function () use ($orders) {
                return $orders->random()->id;
            },
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'created_at' => Carbon::create(2024, 3, 15, 0)
        ]);

        $result = $this->userRepository->getTotalCustomers($merchant, $start_date, $end_date);

        $this->assertEquals($expected_result, $result);
    }

    public function test_get_total_customers_with_invalid_date_range()
    {
        // Assert that the expected exception is thrown
        $this->expectException(UnprocessableException::class);

        $user = User::factory()->create();

        // Define an invalid date range
        $start_date = 'invalid-date';
        $end_date = 'invalid-date';

        $this->userRepository->getTotalCustomers($user, $start_date, $end_date);
    }

    public function test_isinvaliddaterange_returns_false_with_valid_date_range()
    {
        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 3, 20, 0);

        /**
         * @author Tobi Olanitori
         *
         * The isInValidDateRange method is a private one,
         * so it can't be directly tested.
         *
         * To solve this, we use a reflection class.
         */
        // Create a reflection of the user repository
        $userRepositoryReflection = new ReflectionClass($this->userRepository);

        $method = $userRepositoryReflection->getMethod('isInValidDateRange');

        $method->setAccessible(true); // Make the method accessible

        // Call the private method with test data
        $result = $method->invoke($this->userRepository, $start_date, $end_date);

        // Assert the result
        $this->assertFalse($result);

        // Ensure Validator was not updated.
        $this->assertNull($this->userRepository->getValidator());
    }

    public function test_isinvaliddaterange_returns_true_with_invalid_date_range()
    {
        // Define an invalid date range
        $start_date = 'invalid-date';
        $end_date = 'invalid-date';

        // Create a reflection of the user repository
        $userRepositoryReflection = new ReflectionClass($this->userRepository);

        $method = $userRepositoryReflection->getMethod('isInValidDateRange');

        $method->setAccessible(true); // Make the method accessible

        // Call the private method with test data
        $result = $method->invoke($this->userRepository, $start_date, $end_date);

        // Assert the result
        $this->assertTrue($result);

        // Ensure Validator was updated.
        $this->assertNotNull($this->userRepository->getValidator());

        // Assert a validator instance is set
        $this->assertInstanceOf(Validator::class, $this->userRepository->getValidator());
    }
}
