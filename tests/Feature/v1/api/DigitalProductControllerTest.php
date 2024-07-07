<?php

namespace Tests\Feature;

use App\Exceptions\UnAuthorizedException;
use App\Models\Asset;
use App\Models\DigitalProduct;
use App\Models\Product;
use App\Models\User;
use App\Repositories\DigitalProductRepository;
use App\Repositories\ProductRepository;
use App\Repositories\AssetRepository;
use App\Traits\SanctumAuthentication;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\TestCase;

class DigitalProductControllerTest extends TestCase
{
    use RefreshDatabase, SanctumAuthentication;

    protected DigitalProductRepository $digitalProductRepository;

    protected AssetRepository $assetRepository;

    protected ProductRepository $productRepository;

    public function test_store()
    {
        // Create a user
        $user = User::factory()->create();

        $this->actingAs($user);

        Storage::fake('spaces');

        $file_name = 'document.pdf';

        $product = Product::factory()->create([
            'user_id' => $user->id
        ]);

        $file = UploadedFile::fake()->create($file_name, 2048);

        $data = [
            'category' => 'Product',
            'assets' => [$file],
            'product_id' => $product->id,
        ];

        $response = $this->withoutExceptionHandling()->post(route('digitalProduct.store'), $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'product' => ['id', 'thumbnail', 'cover_photos'],
                    'assets' => [['id', 'name', 'url', 'mime_type', 'size', 'extension']],
                ],
            ]);

        $this->assertDatabaseHas('digital_products', [
            'product_id' => $data['product_id'],
            'category' => $data['category'],
        ]);

        $this->assertDatabaseHas('assets', [
            'product_id' => $data['product_id'],
        ]);
    }

    public function test_store_product_not_found()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        Storage::fake('spaces');

        $file = UploadedFile::fake()->create('document.pdf', 2048);

        $data = [
            'category' => 'Product',
            'assets' => [$file],
            'product_id' => 'non-existent-id',
        ];

        $response = $this->post(route('digitalProduct.store'), $data);

        $response->assertNotFound();
    }

    public function test_store_unauthenticated()
    {
        $file = UploadedFile::fake()->create('document.pdf', 2048);

        $data = [
            'category' => 'Product',
            'assets' => [$file],
        ];

        $response = $this->post(route('digitalProduct.store'), $data);

        $response->assertUnauthorized();
    }

    public function test_show_unauthenticated()
    {
        $digitalProduct = DigitalProduct::factory()->create();

        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()
            ->get(route('digitalProduct.show', ['digitalProduct' => $digitalProduct->id]));
    }

    public function test_show_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();

        $this->actingAs($user, 'web');

        $this->withoutExceptionHandling()->get(route('digitalProduct.show', ['digitalProduct' => 1234]));
    }

    public function test_show_forbidden()
    {
        $this->actingAsRegularUser();

        $digital_product = DigitalProduct::factory()->create();

        $response = $this->get(route('digitalProduct.show', ['digitalProduct' => $digital_product->id]));

        $response->assertForbidden();
    }

    public function test_show()
    {
        // Create a user
        $user = $this->actingAsRegularUser();

        $product = Product::factory()->create([
            'user_id' => $user->id
        ]);

        $digital_product = DigitalProduct::factory()->create([
            'product_id' => $product->id
        ]);

        $response = $this->withoutExceptionHandling()
            ->get(route('digitalProduct.show', ['digitalProduct' => $digital_product->id]));

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'category', 'product', 'assets']]);
    }

    public function test_categories()
    {
        $response = $this->get(route('digitalProduct.categories'));

        $response->assertOk();
    }
}
