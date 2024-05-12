<?php

namespace Tests\Feature;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;


    public function test_index(): void
    {
        $user = User::factory()->create();

        $orders = Order::factory(3)->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        $response = $this->actingAs($user, 'web')->get('/api/orders');

        // Convert the orders to OrderResource
        $expected_json = OrderResource::collection($orders)->response()->getData(true);

        // $result_json = $response->json();

        $response->assertStatus(200);


        // $this->assertEquals($expected_json, $result_json);



        // Assert that the response JSON matches the expected JSON
        // $response->assertExactJson($expectedJson);

        // $response->asse
    }
}
