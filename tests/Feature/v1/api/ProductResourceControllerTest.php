<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\TestCase;

class ProductResourceControllerTest extends TestCase
{
    use RefreshDatabase;

    // public function testStore()
    // {
    //   // Create a user
    //   $user = User::factory()->create();
    //   $this->actingAs($user);

    //   Storage::fake('spaces');

    //   $product = Product::factory()->create();
    //   $file = UploadedFile::fake()->create('document.pdf', 100);

    //   $response = $this->postJson('/api/resources/', [
    //       'resource' => $file,
    //       'product_id' => $product->id,
    //   ]);

    //   $response->assertStatus(201);
    // }
}
