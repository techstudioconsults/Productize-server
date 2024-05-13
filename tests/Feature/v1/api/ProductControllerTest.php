<?php

namespace Tests\Feature\v1\api;

use App\Http\Resources\ProductCollection;
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
}
