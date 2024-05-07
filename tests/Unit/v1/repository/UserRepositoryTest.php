<?php

namespace Tests\Unit\v1\repository;

use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnprocessableException;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Carbon;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $userRepository;
    private OrderRepository $orderRepository;

    protected $full_name;
    protected $email;
    protected $password;

    protected $reference_no;
    protected $quantity;

    public function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository();
        $this->full_name = "Tobi Olanitori";
        $this->email = "tobiolanitori@gmail.com";
        $this->password = "password123";

        $this->orderRepository = new OrderRepository();
        $this->reference_no = "123LOLTYP"; // Fixed the typo here
        $this->quantity = 3;
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
        $user = $this->userRepository->createUser($credentials);

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
        $this->userRepository->createUser([
            'full_name' => $this->full_name,
            'password' => $this->password
        ]);
    }

    public function test_update_user()
    {
        $expected_user = User::factory()->create();

        $user = $this->userRepository->update('email', $expected_user->email, [
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

        $this->userRepository->update('email', $user->email, [
            'email' => 'updated@email.com'
        ]);
    }

    public function test_update_user_with_invalid_filter_throws_UnprocessableException()
    {
        $this->expectException(UnprocessableException::class);

        $this->userRepository->update("invalid_column", "inavalid_column", [
            'full_name' => "this will not save",
        ]);
    }

    public function test_update_user_throws_model_not_found_exception()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->userRepository->update("email", "unsavedemail@email.com", [
            'full_name' => "this will not save",
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

    public function test_exception_for_invalid_date_range_throw_UnprocessableException()
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

    public function test_get_total_sales_without_date_range()
    {
        // Arrange
        $user = User::factory()->create();

        // Define an array of order data with custom attributes for each order
        $orderData = [
            [
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
                'reference_no' => fake()->asciify('********************'),
                'quantity' => fake()->numberBetween(1, 10),
                'total_amount' => fake()->randomFloat(2, 10, 100)
            ],
            [
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
                'reference_no' => fake()->asciify('********************'),
                'quantity' => fake()->numberBetween(1, 10),
                'total_amount' => fake()->randomFloat(2, 10, 100)
            ],
            [
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
                'reference_no' => fake()->asciify('********************'),
                'quantity' => fake()->numberBetween(1, 10),
                'total_amount' => fake()->randomFloat(2, 10, 100)
            ]
        ];

        // Create the orders
        foreach ($orderData as $data) {
            Order::create($data);
        }

        // Act
        $totalSales = $this->userRepository->getTotalSales($user);

        // Assert
        $this->assertSame(3, $totalSales);
    }

    public function test_get_total_amount()
    {
        // Arrange
        $user = User::factory()->create();

        // Define an array of order data with custom attributes for each order
        $orderData = [
            [
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
                'reference_no' => fake()->asciify('********************'),
                'quantity' => fake()->numberBetween(1, 10),
                'total_amount' => 10000
            ],
            [
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
                'reference_no' => fake()->asciify('********************'),
                'quantity' => fake()->numberBetween(1, 10),
                'total_amount' => 10000
            ],
            [
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
                'reference_no' => fake()->asciify('********************'),
                'quantity' => fake()->numberBetween(1, 10),
                'total_amount' => 5000
            ]
        ];

        // Create the orders
        foreach ($orderData as $data) {
            Order::create($data);
        }

        $expectedTotalAmount = 10000 + 10000 + 5000;

        $totalAmount = $this->userRepository->getTotalRevenues($user);


        $this->assertSame($expectedTotalAmount, $totalAmount);
    }

    public function test_total_sales_with_date_range()
    {
        // Arrange
        $user = User::factory()->create();

        // Define the date range
        $startDate = Carbon::create(2024, 1, 1, 0);
        $endDate = Carbon::create(2024, 3, 20, 0);

        // Create orders within the specified date range
        $orderData = [
            [
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
                'reference_no' => fake()->asciify('********************'),
                'quantity' => fake()->numberBetween(1, 10),
                'total_amount' => fake()->randomFloat(2, 10, 100),
                'created_at' => Carbon::create(2024, 3, 15, 0),
            ],
            [
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
                'reference_no' => fake()->asciify('********************'),
                'quantity' => fake()->numberBetween(1, 10),
                'total_amount' => fake()->randomFloat(2, 10, 100),
                'created_at' => Carbon::create(2024, 3, 1, 0)
            ],
            [
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
                'reference_no' => fake()->asciify('********************'),
                'quantity' => fake()->numberBetween(1, 10),
                'total_amount' => fake()->randomFloat(2, 10, 100),
                'created_at' => Carbon::create(2024, 2, 15, 0),
            ]
        ];

        foreach ($orderData as $data) {
            Order::create($data);
        }


        // Set up the expected total sales
        $expectedTotalSales = 3;

        // Act
        $totalSales = $this->userRepository->getTotalSales($user, $startDate, $endDate);

        // Assert
        $this->assertSame($expectedTotalSales, $totalSales);
    }

    public function test_exception_for_invalid_date_range_for_revenue_throw_UnprocessableException()
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

    public function test_profileCompletedAt()
    {
        // Arrange
        $user = User::factory()->create([
            'username' => 'testuser',
            'phone_number' => '1234567890',
            'bio' => 'This is a test bio',
            'logo' => 'test-logo.png',
            'twitter_account' => 'testtwitter',
            'facebook_account' => 'testfacebook',
            'youtube_account' => 'testyoutube',
            'profile_completed_at' => now(),
        ]);

        // Act
        $this->userRepository->profileCompletedAt($user);

        // Assert
        $this->assertNotNull($user->profile_completed_at);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'profile_completed_at' => $user->profile_completed_at,
        ]);
    }

    public function test_profileCompletedAt_PropertyIsNull()
    {
        // Arrange
        $user = User::factory()->create([
            'username' => 'testuser',
            'phone_number' => '1234567890',
            'bio' => 'This is a test bio',
            'logo' => 'test-logo.png',
            'twitter_account' => 'testtwitter',
            'facebook_account' => 'testfacebook',
            'youtube_account' => null, // Set one property to null
            'profile_completed_at' => null,
        ]);

        // Act
        $this->userRepository->profileCompletedAt($user);

        // Assert
        $this->assertNull($user->profile_completed_at);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'profile_completed_at' => null,
        ]);
    }
}
