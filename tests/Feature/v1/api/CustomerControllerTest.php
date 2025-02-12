<?php

namespace Tests\Feature;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizedException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Storage;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_authenticated_user_customers()
    {
        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 2, 1, 0);

        $customer_one = User::factory()->create();
        $customer_two = User::factory()->create();

        $merchant = User::factory()->create();

        // Make orders for customer one using merchant's id
        Order::factory()
            ->count(2)
            ->create([
                'user_id' => $customer_one->id,
                'product_id' => Product::factory()->create(['user_id' => $merchant->id]),
            ]);

        // Make orders for customer two using merchant's id
        Order::factory()
            ->count(2)
            ->create([
                'user_id' => $customer_two->id,
                'product_id' => Product::factory()->create(['user_id' => $merchant->id]),
            ]);

        // create customer one
        Customer::factory()->create([
            'merchant_id' => $merchant->id,
            'created_at' => $start_date,
            'user_id' => $customer_one->id,
        ]);

        Customer::factory()->create([
            'merchant_id' => $merchant->id,
            'created_at' => $start_date,
            'user_id' => $customer_two->id,
        ]);

        // Mock the authenticated user
        $this->actingAs($merchant);

        $queryParams = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        // Make a GET request to the index endpoint with mock parameters
        $response = $this->withoutExceptionHandling()->get(route('customer.index', $queryParams));

        // Assert that the response status code is 200
        $response->assertStatus(200);

        // Assert the count of customers in the response
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_unauthenticated_user(): void
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()->get(route('customer.index'));
    }

    public function test_index_not_premium_user_throw_forbidden_exception(): void
    {
        $this->expectException(ForbiddenException::class);

        $user = User::factory()->create(['account_type' => 'free']);

        $this->actingAs($user)
            ->withoutExceptionHandling()
            ->get(route('customer.index'));
    }

    public function test_show_method_returns_customer_resource()
    {
        // Create a user
        $user = User::factory()->create();

        $merchant = User::factory()->create();
        $orders = Order::factory()
            ->count(2)
            ->create([
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $merchant->id]),
            ]);

        // Create a customer associated with the user
        $customer = Customer::factory()->create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'order_id' => $orders->first(),
        ]);

        // Mock authentication
        $this->actingAs($merchant);

        // Make a GET request to the show endpoint
        $response = $this->withoutExceptionHandling()->get(route('customer.show', ['customer' => $customer->id]));

        // Assert that the response status is 200
        $response->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.total_order', 2);
    }

    public function test_show_with_unauthenticated_user_throw_un_authorized_exception(): void
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()->get(route('customer.show', ['customer' => '12345']));
    }

    public function test_download()
    {
        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);

        $customer_one = User::factory()->create();
        $customer_two = User::factory()->create();

        $merchant = User::factory()->create();

        // Make orders for customer one using merchant's id
        Order::factory()
            ->count(2)
            ->create([
                'user_id' => $customer_one->id,
                'product_id' => Product::factory()->create(['user_id' => $merchant->id]),
            ]);

        // Make orders for customer two using merchant's id
        Order::factory()
            ->count(2)
            ->create([
                'user_id' => $customer_two->id,
                'product_id' => Product::factory()->create(['user_id' => $merchant->id]),
            ]);

        // create customer one
        Customer::factory()->create([
            'merchant_id' => $merchant->id,
            'created_at' => $start_date,
            'user_id' => $customer_one->id,
        ]);

        Customer::factory()->create([
            'merchant_id' => $merchant->id,
            'created_at' => $start_date,
            'user_id' => $customer_two->id,
        ]);

        // Mock the authenticated user
        $this->actingAs($merchant);

        // Mock the storage
        Storage::fake('local');

        // Act
        $response = $this
            ->withoutExceptionHandling()
            ->get(route('customer.download'));

        // Assert the response status code
        $response->assertOk();

        // Get the filename used in the downloadList method
        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');

        $fileName = "customers_$now.csv";
        $filePath = 'csv/'.$fileName;

        // Assert the file was created
        Storage::disk('local')->assertExists($filePath);

        // Assert the file content
        $csvContent = Storage::disk('local')->get($filePath);
        $lines = explode("\n", trim($csvContent));

        // Assert the CSV header
        $this->assertEquals('CustomerName,CustomerEmail,LatestPurchase,Price,Date', $lines[0]);

        // Assert the CSV content lines count (5 customers)
        $this->assertCount(3, $lines); // 1 header + 2 data rows

        // Assert the response headers
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename='.$fileName);
    }

    public function test_download_with_date_range(): void
    {
        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 2, 1, 0);

        $customer_one = User::factory()->create();
        $customer_two = User::factory()->create();

        $merchant = User::factory()->create();

        // Make orders for customer one using merchant's id
        Order::factory()
            ->count(2)
            ->create([
                'user_id' => $customer_one->id,
                'product_id' => Product::factory()->create(['user_id' => $merchant->id]),
            ]);

        // Make orders for customer two using merchant's id
        Order::factory()
            ->count(2)
            ->create([
                'user_id' => $customer_two->id,
                'product_id' => Product::factory()->create(['user_id' => $merchant->id]),
            ]);

        // create customer one
        Customer::factory()->create([
            'merchant_id' => $merchant->id,
            'created_at' => $start_date,
            'user_id' => $customer_one->id,
        ]);

        Customer::factory()->create([
            'merchant_id' => $merchant->id,
            'created_at' => $start_date,
            'user_id' => $customer_two->id,
        ]);

        // Mock the authenticated user
        $this->actingAs($merchant);

        $queryParams = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        // Mock the storage
        Storage::fake('local');

        // Act
        $response = $this
            ->withoutExceptionHandling()
            ->get(route('customer.download', $queryParams));

        // Assert the response status code
        $response->assertOk();

        // Get the filename used in the downloadList method
        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');

        $fileName = "customers_$now.csv";
        $filePath = 'csv/'.$fileName;

        // Assert the file was created
        Storage::disk('local')->assertExists($filePath);

        // Assert the file content
        $csvContent = Storage::disk('local')->get($filePath);
        $lines = explode("\n", trim($csvContent));

        // Assert the CSV header
        $this->assertEquals('CustomerName,CustomerEmail,LatestPurchase,Price,Date', $lines[0]);

        // Assert the CSV content lines count (5 customers)
        $this->assertCount(3, $lines); // 1 header + 2 data rows

        // Assert the response headers
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename='.$fileName);
    }
}
