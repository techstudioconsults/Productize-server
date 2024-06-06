<?php

namespace Tests\Feature;

use App\Exceptions\UnAuthorizedException;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private $base_url = "/api/orders";

    public function test_index(): void
    {
        $user = User::factory()->create();

        $expected_count = 3;

        $orders = Order::factory($expected_count)->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        $response = $this->actingAs($user, 'web')->get($this->base_url);

        // Convert the orders to OrderResource
        $expected_json = OrderResource::collection($orders)->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('meta')
                    ->has('links')
                    ->has('data', $expected_count)
                    ->has(
                        'data.0',
                        fn (AssertableJson $json) =>
                        $json->hasAll([
                            'id', 'reference_no', 'product_thumbnail',
                            'product_title', 'product_price', 'customer_name', 'customer_email', 'total_orders',
                            'total_sales', 'total_amount', 'quantity', 'product_publish_date', 'link'
                        ])
                            ->etc()
                    )
            );
    }

    public function test_index_unauthenticated()
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()
            ->get($this->base_url);
    }

    public function test_show()
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        $response = $this->actingAs($user, 'web')->get($this->base_url . '/' . $order->id);

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
            ->get($this->base_url . '/' . $order->id);
    }

    public function test_show_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();

        $this->actingAs($user, 'web')->withoutExceptionHandling()->get($this->base_url . '/1234');
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

        $response = $this->actingAs($user, 'web')->get($this->base_url . '/products/' . $product->id);

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
            ->get($this->base_url . '/products/' . $product->id);
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
            'seen' => true
        ]);

        $response = $this->withoutExceptionHandling()
            ->actingAs($user, 'web')
            ->get(route('order.unseen'));

        $response->assertOk();
        $response->assertJson([
            'data' => ['count' => $expected_count]
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
            'data' => ['message' => 'orders marked as seen']
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
            'seen' => true
        ]);

        $response = $this->withoutExceptionHandling()
            ->actingAs($user, 'web')
            ->patch(route('order.seen.mark'));

        $response->assertOk();
        $response->assertJson([
            'data' => ['message' => 'orders marked as seen']
        ]);

        // Assert all orders are marked as seen
        $this->assertDatabaseHas('orders', [
            'product_id' => $product->id,
            'seen' => 1,
        ]);
    }
}
