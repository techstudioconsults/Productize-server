<?php

namespace Tests\Unit;

use App\Enums\DigitalProductCategory;
use App\Exceptions\ModelCastException;
use App\Models\DigitalProduct;
use App\Models\Product;
use App\Repositories\DigitalProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DigitalProductRepository $digitalProductRepository;

    public function setUp(): void
    {
        parent::setup();
        $this->digitalProductRepository = new DigitalProductRepository();
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

    public function testQuery()
    {
       DigitalProduct::factory()->count(1)->create(['category' => DigitalProductCategory::Product->value]);

       $query = $this->digitalProductRepository->query(['category' => DigitalProductCategory::Product->value ]);

      $result = $query->get();
      $this->assertNotEmpty($result);
      $this->assertEquals(DigitalProductCategory::Product->value, $result->first()->category);
    }

    public function test_query_digital_product_with_filter()
    {
        DigitalProduct::factory()->count(2)->create(['category' => DigitalProductCategory::Product->value]);

        $query = $this->digitalProductRepository->query(['category' => DigitalProductCategory::Product->value]);

        $this->assertEquals(2, $query->count());
    }

    public function test_find_digital_products()
    {
        DigitalProduct::factory()->count(3)->create();

        $products = $this->digitalProductRepository->find(['category' => DigitalProductCategory::Product->value]);

        $this->assertCount(3, $products);
    }
   
    public function test_find_digital_product_by_id()
    {
        $product = DigitalProduct::factory()->create();

        $foundProduct = $this->digitalProductRepository->findById($product->id);

        $this->assertInstanceOf(DigitalProduct::class, $foundProduct);
        $this->assertEquals($product->id, $foundProduct->id);
    }

    public function testQueryWithNonExistentProductCategory()
    {
        DigitalProduct::factory()->count(3)->create();

        $query = $this->digitalProductRepository->query(['category' => 'General Products']);
        $results = $query->get();

        $this->assertCount(0, $results);
    }

    public function test_findbyid_return_null_for_when_not_found(): void
    {
        $result = $this->digitalProductRepository->findById('id_does_not_exist');

        $this->assertNull($result);
    }

    public function test_update()
    {
        $product = Product::factory()->create();
        $digitalProduct = DigitalProduct::factory()->create([
            'product_id' => $product->id,
            'category' => DigitalProductCategory::Product->value,
        ]);

        $updates = [
            'category' => 'Product',
            'product_id' => $product->id,
        ];

        $updateProduct = $this->digitalProductRepository->update($digitalProduct, $updates);

        $this->assertInstanceOf(DigitalProduct::class, $updateProduct);
        $this->assertEquals($digitalProduct->id, $updateProduct->id);
        $this->assertEquals($product->id, $updateProduct->product_id);
        $this->assertDatabaseHas('digital_products', [
            'id' => $digitalProduct->id,
            'category' => $updates['category'],
        ]);
    }

    public function test_update_throws_exception_for_invalid_model()
    {
        // Arrange
        $invalidModel = new Product(); // Using a different model type

        // Act & Assert
        $this->expectException(ModelCastException::class);
        $this->digitalProductRepository->update($invalidModel, ['category' => DigitalProductCategory::Product->value]);
    }
    
}
