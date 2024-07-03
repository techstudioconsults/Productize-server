<?php

namespace Tests\Feature;

use App\Enums\DigitalProductCategory;
use App\Exceptions\UnAuthorizedException;
use App\Models\DigitalProduct;
use App\Models\Product;
use App\Models\ProductResource;
use App\Models\User;
use App\Repositories\DigitalProductRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductResourceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Storage;
use Tests\TestCase;

class DigitalProductControllerTest extends TestCase
{
    use RefreshDatabase;
    protected DigitalProductRepository $digitalProductRepository;
    protected ProductResourceRepository $productResourceRepository;
    protected ProductRepository $productRepository;


    public function testStore()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        Storage::fake('spaces');

        $product = Product::factory()->create([
            'thumbnail' => 'path/to/thumbnail.jpg',
            'cover_photos' => ['path/to/cover1.jpg', 'path/to/cover2.jpg'],
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 2048);

        $data = [
            'category' => 'Product',
            'resources' => [$file],
            'product_id' => $product->id,
        ];

        $digitalProduct = DigitalProduct::factory()->make(['product_id' => $product->id]);
        $resource = ProductResource::factory()->make([
            'product_id' => $product->id,
            'name' => 'document.pdf',
            'url' => 'path/to/document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 102400, // 100KB
            'extension' => 'pdf',
        ]);

        $response = $this->postJson('/api/digitalProducts', $data);


        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'product' => ['id', 'thumbnail', 'cover_photos'],
                    'resources' => [['id', 'name', 'url', 'mime_type', 'size', 'extension']],
                ],
            ]);
        $this->assertDatabaseHas('digital_products', [
            'product_id' => $data['product_id'],
            'category' => $data['category'],
        ]);
    }

    public function testStoreProductNotFound()
    {
         // Create a user
         $user = User::factory()->create();
         $this->actingAs($user);

         Storage::fake('spaces');

         $file = UploadedFile::fake()->create('document.pdf', 2048);

        $data = [
            'category' => 'Product',
            'resources' => [$file],
            'product_id' => 'non-existent-id',
        ];

        $response = $this->postJson('/api/digitalProducts', $data);

        $response->assertStatus(422);
    }

    public function test_show_unauthenticated()
    {
        //create product
        $product = Product::factory()->create();

        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()
            ->get('api/digitalProducts/products/' . $product->id);
    }

    public function test_show_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();

        $this->actingAs($user, 'web')->withoutExceptionHandling()->get(route('digitalProduct.show', ['product' => '1234']));
    }

    public function testStoreWithInvalidData()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

      
        $data = [
            'category' => 'Product',
            'resources' => '',
            'product_id' => ''
        ];

        $response = $this->postJson('/api/digitalProducts', $data);

        $response->assertStatus(422);
    }

    public function testCategories()
    {
        $response = $this->getJson('/api/digitalProducts/categories');

        $response->assertStatus(200);
    }

    // public function testShow()
    // {
    //       // Create a user
    //       $user = User::factory()->create();
    //       $this->actingAs($user);

    //     $product = Product::factory()->create();
    //     $digitalProduct = DigitalProduct::factory()->make(['product_id' => $product->id]);

    //     $response = $this->getJson("/api/digitalProducts/products/{$product->id}");

    //     $response->assertStatus(200)
    //         ->assertJsonStructure(['data' => ['id', 'category', 'product_id']]);
    // }
}
