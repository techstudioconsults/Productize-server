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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Storage;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $userRepository;

    protected $full_name;

    protected $email;

    protected $password;

    protected function setUp(): void
    {
        parent::setUp();

        $this->full_name = 'Tobi Olanitori';
        $this->email = 'tobiolanitori@gmail.com';
        $this->password = 'password123';

        $this->userRepository = app(UserRepository::class);
    }

    /**
     * Test Create User
     */
    public function test_create_user(): void
    {
        $credentials = [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'password' => $this->password,
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
        $this->assertEquals('free_trial', $user->account_type);
    }

    public function test_create_user_with_no_email_throws_bad_request_exception()
    {
        $this->expectException(BadRequestException::class);

        // Attempt to create a user without an email
        $this->userRepository->create([
            'full_name' => $this->full_name,
            'password' => $this->password,
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
            'payout_notification' => true,
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

        $this->assertTrue((bool) $user->product_creation_notification);

        $this->assertTrue((bool) $user->news_and_update_notification);

        $this->assertTrue((bool) $user->payout_notification);
    }

    public function test_update_user_email_should_throw_bad_request_exception()
    {
        $this->expectException(BadRequestException::class);

        $user = User::factory()->create();

        $this->userRepository->update($user, [
            'email' => 'updated@email.com',
        ]);
    }

    public function test_guarded_update()
    {
        $expected_user = User::factory()->create();

        $expected_result = 'updated';

        $user = $this->userRepository->guardedUpdate($expected_user->email, 'full_name', $expected_result);

        // Assert name has changed
        assertEquals($user->full_name, $expected_result);

        // Assert a user instance is returned
        $this->assertInstanceOf(User::class, $user);
    }

    public function test_guarded_update_email_should_throw_bad_request()
    {
        $this->expectException(BadRequestException::class);

        $user = User::factory()->create();

        $this->userRepository->guardedUpdate($user->email, 'email', 'updated@email');
    }

    public function test_guarded_update_column_not_found_should_throw_unprocessable_exception()
    {
        $this->expectException(UnprocessableException::class);

        $user = User::factory()->create();

        $this->userRepository->guardedUpdate($user->email, 'doesnt_exit', 'unprocessable');
    }

    public function test_guarded_update_email_not_found_should_throw_not_found_exception()
    {
        $this->expectException(NotFoundException::class);

        $this->userRepository->guardedUpdate($this->email, 'full_name', 'not found');
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
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 3, 20, 0);

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

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        // Act
        $totalSales = $this->userRepository->getTotalSales($user, $filter);

        // Assert
        $this->assertEquals($expectedTotalSales, $totalSales);
    }

    public function test_get_total_sales_with_invalid_date_range_should_throw_unprocessable_exception()
    {
        // Arrange
        $user = User::factory()->create();

        // Define an invalid date range
        $start_date = 'invalid-date';
        $end_date = 'invalid-date';

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        // Assert that the expected exception is thrown
        $this->expectException(UnprocessableException::class);

        // Act
        $this->userRepository->getTotalSales($user, $filter);
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
                'created_at' => Carbon::create(2024, 3, 15, 0),
            ]);

        // Create an order outside the date range
        Order::factory()->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
            'total_amount' => 80000,
        ]);

        $expected_result = $amount1 + $amount2 + $amount3;

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $result = $this->userRepository->getTotalRevenues($user, $filter);

        $this->assertEquals($expected_result, $result);
    }

    public function test_get_revenue_throw_unprocessable_exception_for_invalid_date_range()
    {
        // Arrange
        $user = User::factory()->create();

        // Define an invalid date range
        $start_date = 'invalid-date';
        $end_date = 'invalid-date';

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        // Assert that the expected exception is thrown
        $this->expectException(UnprocessableException::class);

        // Act
        $this->userRepository->getTotalRevenues($user, $filter);
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
            },
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
            'created_at' => Carbon::create(2024, 3, 15, 0),
        ]);

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $result = $this->userRepository->getTotalCustomers($merchant, $filter);

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

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $this->userRepository->getTotalCustomers($user, $filter);
    }

    public function test_first_or_create_user_already_exists()
    {
        // Arrange: Create a user with the given email
        $email = 'existing@example.com';
        $name = 'Existing User';

        $existingUser = User::factory()->create(['email' => $email, 'full_name' => $name]);

        $user = $this->userRepository->firstOrCreate($email, 'Different Name');

        // Assert: The user returned should be the existing user
        $this->assertEquals($existingUser->id, $user->id);
        $this->assertEquals($email, $user->email);
        $this->assertEquals($name, $user->full_name);
    }

    public function test_first_or_create_user_does_not_exist()
    {
        // Arrange: Define the email and name for a new user
        $email = 'new@example.com';
        $name = 'New User';

        // Act: Call the method
        $user = $this->userRepository->firstOrCreate($email, $name);

        // Assert: A new user should be created with the given email and name
        $this->assertNotNull($user);
        $this->assertEquals($email, $user->email);
        $this->assertEquals($name, $user->full_name);
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'full_name' => $name,
        ]);
    }

    public function test_upload_document_image(): void
    {
        // Fake spaces storage
        Storage::fake('spaces');

        $file_name = 'document.png';

        $document = UploadedFile::fake()->image($file_name);

        $expected_result = config('filesystems.disks.spaces.cdn_endpoint').'/'.UserRepository::KYCDOCUMENT_PATH."/$file_name";

        $result = $this->userRepository->uploadDocumentImage($document);

        Storage::disk('spaces')->assertExists(UserRepository::KYCDOCUMENT_PATH."/$file_name");

        $this->assertEquals($expected_result, $result);
    }

    public function test_upload_document_invalid_image(): void
    {
        $this->expectException(BadRequestException::class);

        $this->userRepository->uploadDocumentImage(UploadedFile::fake()->create('not_an_image.pdf'));
    }

    public function test_update_kyc(): void
    {
        $expected_user = User::factory()->create();

        // Fake spaces storage
        Storage::fake('spaces');

        $user = $this->userRepository->update($expected_user, [
            'country' => 'Nigeria',
            'document_type' => 'National Id card',
            'document_image' => UploadedFile::fake()->image('document_image.jpg'),
        ]);

        // Assert that the user's information was updated correctly
        $this->assertEquals($user->country, 'Nigeria');
        $this->assertEquals($user->document_type, 'National Id card');

        // Assert that the document image was stored
        $this->assertNotEmpty($user->document_image);

        // Extract the file path from the full URL
        $filePath = str_replace('https://productize.nyc3.cdn.digitaloceanspaces.com/', '', $user->document_image);

        // Assert that the file exists in the faked storage
        Storage::disk('spaces')->assertExists($filePath);

        // Optionally, you can check if the stored file name contains the original file name
        $this->assertStringContainsString('document_image', $filePath);
    }
}
