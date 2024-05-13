<?php

namespace Tests\Feature\v1\api;

use App\Http\Resources\ProductCollection;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private ProductRepository $productRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->productRepository = app(ProductRepository::class);

        $this->productRepository->seed();
    }

    public function test_index(): void
    {
        $response = $this->get(route('product.index'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->has('meta')
                ->has('links')
                ->has('data', 15)
                ->has(
                    'data.0',
                    fn (AssertableJson $json) =>
                    $json
                        ->hasAll([
                            'title', 'thumbnail', 'price',
                            'publisher', 'slug', 'highlights', 'product_type', 'cover_photos',
                            'tags', 'description', 'status'
                        ])
                        ->where('status', 'published')
                        ->missing('id')
                        ->etc()
                )
        );
    }

    public function test_top_products(): void
    {
        $products = Product::factory()
            ->count(5)
            ->has(
                Order::factory()
                    ->count(3)
                    ->state(function () {
                        return ['quantity' => 2];
                    })
            )
            ->create(['user_id' => User::factory()->create(), 'price' => 200000]);

        $response = $this->get(route('product.top-products'));

        $expected_json = ProductCollection::make($products)->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);
    }

    public function test_top_products_no_order(): void
    {
        $response = $this->get(route('product.top-products'));

        $expected_json = ProductCollection::make([])->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);
    }
}
