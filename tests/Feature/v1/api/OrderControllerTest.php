<?php

namespace Tests\Feature;

use App\Exceptions\UnAuthorizedException;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\OrderSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Tests\Traits\SanctumAuthentication;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase, SanctumAuthentication;

    public function test_super_admin_can_access_index()
    {
        $this->actingAsSuperAdmin();

        // Create orders for testing
        $this->seed(OrderSeeder::class);

        $expected_json = OrderResource::collection(Order::all())->response()->getData(true);

        // Call the index endpoint
        $response = $this->withoutExceptionHandling()->get(route('order.index'));

        // Assert response is successful
        $response->assertOk()->assertJson($expected_json, true);

        // Assert response structure
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'reference_no', 'quantity', 'total_amount', 'product', 'customer', 'created_at'],
            ],
            'links',
            'meta',
        ]);
    }

    public function test_non_super_admin_cannot_access_index()
    {
        $this->actingAsRegularUser();

        // Call the index endpoint
        $response = $this->get(route('order.index'));

        // Assert forbidden response
        $response->assertForbidden();
    }

    public function test_index_with_filters()
    {
        $this->actingAsSuperAdmin();

        // Create orders for testing
        $orders = Order::factory()->count(2)->create([
            'product_id' => Product::factory()->create(['title' => 'Product A']),
            'created_at' => now()->subDays(5),
        ]);

        Order::factory()->create([
            'product_id' => Product::factory()->create(['title' => 'Product B']),
            'created_at' => now()->subDays(10),
        ]);

        $expected_json = OrderResource::collection($orders)->response()->getData(true);

        // Call the index endpoint with filters
        $response = $this->get(route('order.index', [
            'product_title' => 'Product A',
            'start_date' => now()->subDays(6)->toDateString(),
            'end_date' => now()->subDays(4)->toDateString(),
        ]));

        // Assert response is successful
        $response->assertOk()->assertJson($expected_json, true);

        // Assert the filtered orders are returned
        $response->assertJsonCount(2, 'data');
    }

    public function test_user(): void
    {
        $user = User::factory()->create();

        $expected_count = 3;

        $orders = Order::factory($expected_count)->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        $response = $this->actingAs($user, 'web')->get(route('order.user'));

        // Convert the orders to OrderResource
        $expected_json = OrderResource::collection($orders)->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);

        $response
            ->assertJson(
                fn (AssertableJson $json) => $json->has('meta')
                    ->has('links')
                    ->has('data', $expected_count)
                    ->has(
                        'data.0',
                        fn (AssertableJson $json) => $json->hasAll([
                            'id', 'reference_no', 'quantity', 'total_amount', 'product', 'customer', 'created_at',
                        ])
                            ->etc()
                    )
            );
    }

    public function test_user_unauthenticated()
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()
            ->get(route('order.user'));
    }

    public function test_show()
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        $response = $this->actingAs($user, 'web')->get(route('order.show', ['order' => $order->id]));

        // Convert the orders to OrderResource
        $expected_json = OrderResource::make($order)->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);
    }

    public function test_show_unathenticated()
    {
        $this->expectException(UnAuthorizedException::class);

        $user = User::factory()->create();

        $order = Order::factory()->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        $this->withoutExceptionHandling()
            ->get(route('order.show', ['order' => $order->id]));
    }

    public function test_show_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();

        $this->actingAs($user, 'web')->withoutExceptionHandling()->get(route('order.show', ['order' => '1234']));
    }

    public function test_show_by_product()
    {
        $user = User::factory()->create();

        $expected_count = 3;

        $product = Product::factory()->create(['user_id' => $user->id]);

        $orders = Order::factory($expected_count)->create([
            'product_id' => $product->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        $response = $this->actingAs($user, 'web')->get(route('order.show.product', ['product' => $product->id]));

        // Convert the orders to OrderResource
        $expected_json = OrderResource::collection($orders)->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);
    }

    public function test_show_by_product_unauthenticated()
    {
        $this->expectException(UnAuthorizedException::class);

        $user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $this->withoutExceptionHandling()
            ->get(route('order.show.product', ['product' => $product->id]));
    }

    public function test_show_by_customer()
    {
        $user = User::factory()->create();

        $merchant = User::factory()->create();

        $this->actingAs($merchant);

        // Create customer and orders for testing
        $customer = Customer::factory()->create([
            'user_id' => $user->id,
            'order_id' => Order::factory()->create([
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $merchant->id])
            ])->id,
            'merchant_id' => $merchant->id,
        ]);

        $customer = Customer::factory()->create([
            'user_id' => $user->id,
            'order_id' => Order::factory()->create([
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $merchant->id])
            ])->id,
            'merchant_id' => $merchant->id,
        ]);

        $customer = Customer::factory()->create([
            'user_id' => $user->id,
            'order_id' => Order::factory()->create([
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $merchant->id])
            ])->id,
            'merchant_id' => $merchant->id,
        ]);

        // Call the showByCustomer endpoint
        $response = $this->withoutExceptionHandling()->get(route('order.show.customer', ['customer' => $customer->first()->id]));

        // Assert response is successful
        $response->assertOk();

        // Assert the correct orders are returned
        $response->assertJsonCount(3, 'data');
    }

    public function test_unseen(): void
    {
        $user = User::factory()->create();

        $expected_count = 3;

        $product = Product::factory()->create(['user_id' => $user->id]);

        Order::factory($expected_count)->create([
            'product_id' => $product->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        // create a seen order
        Order::factory()->create([
            'product_id' => $product->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
            'seen' => true,
        ]);

        $response = $this->withoutExceptionHandling()
            ->actingAs($user, 'web')
            ->get(route('order.unseen'));

        $response->assertOk();
        $response->assertJson([
            'data' => ['count' => $expected_count],
        ]);
    }

    public function test_unseen_unauthenticated(): void
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()->get(route('order.unseen'));
    }

    public function test_markseen(): void
    {
        $user = User::factory()->create();

        $expected_count = 3;

        $product = Product::factory()->create(['user_id' => $user->id]);

        Order::factory($expected_count)->create([
            'product_id' => $product->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        $response = $this->withoutExceptionHandling()
            ->actingAs($user, 'web')
            ->patch(route('order.seen.mark'));

        $response->assertOk();
        $response->assertJson([
            'data' => ['message' => 'orders marked as seen'],
        ]);

        // Assert all orders are marked as seen
        $this->assertDatabaseHas('orders', [
            'product_id' => $product->id,
            'seen' => 1,
        ]);
    }

    public function test_markseen_unauthenticated()
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()->patch(route('order.seen.mark'));
    }

    /**
     * Test marking orders as seen when there are no unseen orders.
     */
    public function test_markseen_orders_as_seen_when_no_unseen_orders()
    {

        $user = User::factory()->create();

        $expected_count = 3;

        $product = Product::factory()->create(['user_id' => $user->id]);

        Order::factory($expected_count)->create([
            'product_id' => $product->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
            'seen' => true,
        ]);

        $response = $this->withoutExceptionHandling()
            ->actingAs($user, 'web')
            ->patch(route('order.seen.mark'));

        $response->assertOk();
        $response->assertJson([
            'data' => ['message' => 'orders marked as seen'],
        ]);

        // Assert all orders are marked as seen
        $this->assertDatabaseHas('orders', [
            'product_id' => $product->id,
            'seen' => 1,
        ]);
    }

    public function test_super_admin_can_view_order_stats()
    {
        $this->actingAsSuperAdmin();

        // Create orders for testing
        Order::factory()->create(['total_amount' => 100]);
        Order::factory()->create(['total_amount' => 200]);

        // Call the stats endpoint
        $response = $this->get(route('order.stats'));

        // Assert response is successful
        $response->assertOk();

        // Assert response structure and values
        $response->assertJson([
            'data' => [
                'total_orders' => 2,
                'total_orders_revenue' => 300,
                'avg_order_value' => 150,
            ]
        ]);
    }

    public function test_non_super_admin_cannot_view_order_stats()
    {
        $this->actingAsRegularUser();

        // Call the stats endpoint
        $response = $this->get(route('order.stats'));

        // Assert forbidden response
        $response->assertForbidden();
    }

    public function test_order_stats_with_no_orders()
    {
        $this->actingAsSuperAdmin();

        // Call the stats endpoint
        $response = $this->get(route('order.stats'));

        // Assert response is successful and values are zero
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'total_orders' => 0,
                'total_orders_revenue' => 0,
                'avg_order_value' => 0,
            ]
        ]);
    }
}
