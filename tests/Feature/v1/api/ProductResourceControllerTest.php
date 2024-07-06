<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Traits\SanctumAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\TestCase;

class ProductResourceControllerTest extends TestCase
{
    use RefreshDatabase, SanctumAuthentication;

    public function test_store()
    {
        Storage::fake('spaces');

        // Create a user
        $user = $this->actingAsRegularUser();

        $product = Product::factory()->create([
            'user_id' => $user->id
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->withoutExceptionHandling()->post(route('resources.store'), [
            'resource' => $file,
            'product_id' => $product->id,
        ]);

        $response->assertCreated();
    }

    public function test_store_throws_403_when_product_does_not_belong_to_authorized_user()
    {
        Storage::fake('spaces');

        // Create a user
        $this->actingAsRegularUser();

        $product = Product::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->post(route('resources.store'), [
            'resource' => $file,
            'product_id' => $product->id,
        ]);

        $response->assertForbidden();
    }

    public function test_store_throws_404_when_the_product_not_found()
    {
        Storage::fake('spaces');

        // Create a user
        $this->actingAsRegularUser();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->post(route('resources.store'), [
            'resource' => $file,
            'product_id' => "12345",
        ]);

        $response->assertNotFound();
    }
}
