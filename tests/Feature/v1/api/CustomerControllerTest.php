<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Storage;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_authenticated_customers()
    {
        // Create a user for testing
        $user = User::factory()->create();
        $merchant = User::factory()->create(); // Create a separate user as the merchant

        // Create products associated with the merchant user
        $product = Product::factory()->create(['user_id' => $merchant->id]);

        // Create some orders associated with the products
        $order = Order::factory()->create(['product_id' => $product->id]);

        // Create some customers for the user with orders associated with the merchant's products
        Customer::factory()->count(5)->create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id, // Associate with the merchant user
            'order_id' => $order->id
        ]);

        // Mock the authenticated user
        $this->actingAs($user);

        // Define the request parameters
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 12, 31, 0);
        $requestParams = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        // Make a GET request to the index endpoint with mock parameters
        $response = $this->get('/api/customers', $requestParams);

        // Assert that the response status code is 200
        $response->assertStatus(200);

        // Assert the count of customers in the response
        $response->assertJsonCount(5, 'data');
    }

    public function test_show_method_returns_customer_resource()
    {
        // Create a user
        $user = User::factory()->create();

        // Create Product and Order
        $product = Product::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['product_id' => $product->id]);

        // Create a customer associated with the user
        $customer = Customer::factory()->create([
            'user_id' => $user->id,
            'merchant_id' => $user->id, // Associate with the user as merchant
            'order_id' => $order->id
        ]);

        // Mock authentication
        $this->actingAs($user);

        // Make a GET request to the show endpoint
        $response = $this->get('/api/customers/' . $customer->id);

        // Assert that the response status is 200
        $response->assertStatus(200);

        // Assert that the response data matches the expected data
        $response->assertJson([
            'id' => $customer->id,
            'user_id' => $customer->user_id,
            'merchant_id' => $customer->merchant_id,
            'order_id' => $customer->order_id,
            // Add other customer fields as necessary
        ]);
    }

    public function test_download_list()
    {
        // Arrange
        $user = User::factory()->create();
        $merchant = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $merchant->id]);

        $order = Order::factory()->create(['product_id' => $product->id]);

        Customer::factory()->count(5)->create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'order_id' => $order->id,
            'created_at' => Carbon::now()->subDays(1)
        ]);

        // Mock authentication
        $this->actingAs($user);

        // Define request parameters
        $startDate = Carbon::now()->subDays(2)->toDateString();
        $endDate = Carbon::now()->toDateString();

        $requestParams = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        // Mock the storage
        Storage::fake('local');

        // Act
        $response = $this->get('/api/customers/download', $requestParams);

        // Assert the response status code
        $response->assertStatus(200);

        // Get the filename used in the downloadList method
        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');
        $fileName = "customers_$now.csv";
        $filePath = 'csv/' . $fileName;

        // Assert the file was created
        Storage::disk('local')->assertExists($filePath);

        // Assert the file content
        $csvContent = Storage::disk('local')->get($filePath);
        $lines = explode("\n", trim($csvContent));

        // Assert the CSV header
        $this->assertEquals('CustomerName,CustomerEmail,LatestPurchase,Price,Date', $lines[0]);

        // Assert the CSV content lines count (5 customers)
        $this->assertCount(6, $lines); // 1 header + 5 data rows

        // Assert the response headers
        $response->assertHeader('Content-Type', 'text/csv');
        $response->assertHeader('Content-Disposition', 'attachment; filename=' . $fileName);

        // Clean up by deleting the created file
        Storage::disk('local')->delete($filePath);
    }
}
