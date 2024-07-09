<?php

namespace Feature\V1\Api;

use App\Models\Asset;
use App\Models\Product;
use App\Traits\SanctumAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\TestCase;

class AssetControllerTest extends TestCase
{
    use RefreshDatabase, SanctumAuthentication;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_store()
    {
        Storage::fake('spaces');

        $user = $this->actingAsRegularUser();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $file = UploadedFile::fake()->image('asset.jpg');

        $response = $this->withoutExceptionHandling()->post(route('assets.store'), [
            'product_id' => $product->id,
            'asset' => $file,
        ]);

        $response->assertCreated(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'url',
                    'size',
                    'mime_type',
                    'extension',
                    'publisher',
                ],
            ]);

        // Additional assertions to ensure data integrity
        $this->assertDatabaseHas('assets', [
            'name' => str_replace(' ', '', $file->getClientOriginalName()),
            'product_id' => $product->id,
        ]);
    }

    public function test_store_unauthenticated()
    {
        $product = Product::factory()->create();

        $file = UploadedFile::fake()->image('asset.jpg');

        $response = $this->post(route('assets.store'), [
            'product_id' => $product->id,
            'asset' => $file,
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_throw_404_when_product_is_not_found()
    {
        $this->actingAsRegularUser();

        $file = UploadedFile::fake()->image('asset.jpg');

        $response = $this->post(route('assets.store'), [
            'product_id' => 1234,
            'asset' => $file,
        ]);

        $response->assertNotFound();
    }

    public function test_store_throw_403_when_product_does_not_belong_to_user()
    {
        $this->actingAsRegularUser();

        $product = Product::factory()->create();

        $file = UploadedFile::fake()->image('asset.jpg');

        $response = $this->post(route('assets.store'), [
            'product_id' => $product->id,
            'asset' => $file,
        ]);

        $response->assertForbidden();
    }

    public function test_product()
    {
        $user = $this->actingAsRegularUser();

        $product = Product::factory()->create(['user_id' => $user->id]);

        Asset::factory()->count(5)->create(['product_id' => $product->id]);

        $response = $this->withoutExceptionHandling()->get(route('assets.product', ['product' => $product->id]));

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'url',
                        'size',
                        'mime_type',
                        'extension',
                        'publisher',
                    ],
                ],
            ]);
    }

    public function test_product_unauthenticated()
    {
        $product = Product::factory()->create();

        $response = $this->get(route('assets.product', ['product' => $product->id]));

        $response->assertUnauthorized();
    }

    public function test_product_not_found()
    {
        $this->actingAsRegularUser();

        $response = $this->get(route('assets.product', ['product' => 1234]));

        $response->assertNotFound();
    }

    public function test_delete()
    {
        $user = $this->actingAsRegularUser();

        $product = Product::factory()->create([
            'user_id' => $user->id,
        ]);

        $asset = Asset::factory()->create([
            'product_id' => $product->id,
        ]);

        $response = $this->withoutExceptionHandling()->delete(route('assets.delete', ['asset' => $asset->id]));

        $response->assertStatus(200);

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }

    public function test_delete_forbidden()
    {
        $this->actingAsRegularUser();

        $product = Product::factory()->create();

        $asset = Asset::factory()->create([
            'product_id' => $product->id,
        ]);

        $response = $this->delete(route('assets.delete', ['asset' => $asset->id]));

        $response->assertForbidden();
    }

    public function test_delete_unauthenticated()
    {
        $product = Product::factory()->create();

        $asset = Asset::factory()->create([
            'product_id' => $product->id,
        ]);

        $response = $this->delete(route('assets.delete', ['asset' => $asset->id]));

        $response->assertUnauthorized();
    }

    public function test_delete_not_found()
    {
        $this->actingAsRegularUser();

        $response = $this->delete(route('assets.delete', ['asset' => 12345]));

        $response->assertNotFound();
    }
}
