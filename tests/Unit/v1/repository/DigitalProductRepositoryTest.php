<?php

namespace Tests\Unit;

use App\Models\DigitalProduct;
use App\Models\Product;
use App\Repositories\DigitalProductRepository;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected DigitalProductRepository $digitalProductRepository;

    protected function setUp(): void
    {
        parent::setup();
        $this->digitalProductRepository = new DigitalProductRepository;
    }

    public function test_create()
    {
        $product = Product::factory()->create();

        $data = [
            'category' => 'Product',
            'product_id' => $product->id,
        ];

        $digitalProduct = $this->digitalProductRepository->create($data);

        $this->assertInstanceOf(DigitalProduct::class, $digitalProduct);
        $this->assertDatabaseHas('digital_products', $data);
    }

    public function test_query()
    {
        DigitalProduct::factory()->count(5)->create(['created_at' => now()->subDays(5)]);
        DigitalProduct::factory()->count(5)->create(['created_at' => now()->subDays(10)]);

        $filter = [
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->toDateString(),
        ];

        $query = $this->digitalProductRepository->query($filter);

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertCount(5, $query->get());
    }

    public function test_find()
    {
        DigitalProduct::factory()->count(5)->create();

        $products = $this->digitalProductRepository->find();

        $this->assertInstanceOf(Collection::class, $products);
        $this->assertCount(5, $products);
    }

    public function test_find_by_id()
    {
        $product = DigitalProduct::factory()->create();
        $foundProduct = $this->digitalProductRepository->findById($product->id);

        $this->assertInstanceOf(DigitalProduct::class, $foundProduct);
        $this->assertEquals($product->id, $foundProduct->id);
    }

    public function test_find_by_id_returns_null_for_invalid_id()
    {
        $foundProduct = $this->digitalProductRepository->findById('invalid-id');

        $this->assertNull($foundProduct);
    }

    public function test_find_one()
    {
        $product = Product::factory()->create();

        $expected = DigitalProduct::factory()->create([
            'product_id' => $product->id,
        ]);

        $filter = ['product_id' => $product->id];

        $result = $this->digitalProductRepository->findOne($filter);

        $this->assertInstanceOf(DigitalProduct::class, $result);

        $this->assertEquals($product->id, $result->product->id);
        $this->assertEquals($expected->id, $result->id);
    }

    public function test_find_one_returns_null_for_no_match()
    {
        $filter = ['product_id' => 'invalid-id'];

        $foundProduct = $this->digitalProductRepository->findOne($filter);

        $this->assertNull($foundProduct);
    }
}
