<?php

namespace Tests\Unit;

use App\Models\DigitalProduct;
use App\Models\Product;
use App\Repositories\DigitalProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalProductRepositoryTest extends TestCase
{

  use RefreshDatabase;

  protected $digitalProductRepository;

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

    // public function testQuery()
    // {
    //    DigitalProduct::factory()->count(2)->create();

    //    $query = $this->digitalProductRepository->query(['category' => 'EBOOK']);

    //    $this->assertInstanceOf(DigitalProduct::class, $query);
    // }
}
