<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\TestCase;

class ProductResourceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_product_resource()
    {
        Storage::fake('spaces');

        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id, 'product_type' => 'digital_product']);

        $response = $this->actingAs($user)->postJson('/api/resources/', [
            'resource' => UploadedFile::fake()->create('document.pdf', 100),
            'product_id' => $product->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'mime_type',
                    'size',
                    'extension',
                    'url',
                    'publisher',
                ]
            ]);

        $this->assertDatabaseHas('product_resources', [
            'product_id' => $product->id,
        ]);
    }

    public function test_retrieve_resources_for_a_product()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['user_id' => $user->id]);
        ProductResource::factory()->count(3)->create(['product_id' => $product->id]);

        $response = $this->getJson('/api/products/' . $product->id);

        $response->assertStatus(200);
    }

    public function test_cannot_store_resource_for_non_existent_product()
    {

        Storage::fake('spaces');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/products/', [
            'resource' => UploadedFile::fake()->create('document.pdf', 100),
            'product_id' => 'non-existent-id',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_delete_a_resource()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['user_id' => $user->id]);

        $resource = ProductResource::factory()->create(['product_id' => $product->id]);

        $response = $this->deleteJson("/api/resources");

        $response->assertStatus(200);
    }

    public function test_create_resource_with_invalid_data()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/resources', [
            'resource' => 'not a file',
            'product_id' => 'invalid-id',
        ]);

        $response->assertStatus(422);
    }
}
