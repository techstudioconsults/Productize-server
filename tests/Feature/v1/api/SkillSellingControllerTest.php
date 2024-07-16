<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 02-07-2024
 */

namespace Tests\Feature;

use App\Enums\SkillSellingCategory;
use App\Exceptions\UnAuthorizedException;
use App\Http\Resources\SkillSellingResource;
use App\Models\Product;
use App\Models\SkillSelling;
use App\Models\User;
use App\Traits\SanctumAuthentication;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\TestCase;

class SkillSellingControllerTest extends TestCase
{
    use RefreshDatabase, SanctumAuthentication;

    public function test_store(): void
    {
        $user = $this->actingAsRegularUser();

        Storage::fake('spaces');

        $product = Product::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'category' => 'Product',
            'product_id' => $product->id,
            'category' => 'Product',
            'link' => 'https://www.github.com',
            'resource_link' => ['https://www.github.com', 'https://www.github.com']
        ];

        $response = $this->withoutExceptionHandling()->post(route('skillSelling.store'), $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'category',
                    'link',
                    'resource_link',
                    'created_at',
                    'product' => ['id', 'thumbnail', 'cover_photos'],
                ],
            ]);

        $this->assertDatabaseHas('skill_sellings', [
            'product_id' => $data['product_id'],
            'category' => $data['category'],
        ]);
    }

    public function test_store_product_not_found()
    {
        $this->actingAsRegularUser();

        Storage::fake('spaces');

        $file = UploadedFile::fake()->create('document.pdf', 2048);

        $data = [
            'category' => 'Product',
            'assets' => [$file],
            'product_id' => 'non-existent-id',
            'level' => 'high',
            'availability' => 'yes',
            'category' => 'Product',
            'link' => 'www.github.com',
        ];

        $response = $this->post(route('skillSelling.store'), $data);

        $response->assertNotFound();
    }

    public function test_store_unauthenticated()
    {
        $file = UploadedFile::fake()->create('document.pdf', 2048);

        $data = [
            'category' => 'Product',
            'assets' => [$file],
        ];

        $response = $this->post(route('skillSelling.store'), $data);

        $response->assertUnauthorized();
    }

    public function test_store_forbidden()
    {
        $this->actingAsRegularUser();

        $product = Product::factory()->create(); // create a product for another user

        $data = [
            'product_id' => $product->id,
        ];

        $response = $this->post(route('skillSelling.store'), $data);

        $response->assertForbidden();
    }

    public function test_show(): void
    {
        // Create a user
        $user = $this->actingAsRegularUser();

        //create product
        $product = Product::factory()->create(
            ['user_id' => $user->id]
        );

        // Create a skillselling
        $skillSelling = SkillSelling::factory()->create([
            'category' => 'Product',
            'link' => 'www.github.com',
            'product_id' => $product->id,
        ]);

        // Invoke the show method
        $response = $this->withoutExceptionHandling()->get(route('skillSelling.show', ['skillSelling' => $skillSelling->id]));

        $expected_json = SkillSellingResource::make($skillSelling)->response()->getData(true);

        $response->assertOk()->assertJson($expected_json, true);
    }

    public function test_show_unauthenticated()
    {
        $skillSelling = SkillSelling::factory()->create();

        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()
            ->get(route('skillSelling.show', ['skillSelling' => $skillSelling->id]));
    }

    public function test_show_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();

        $this->actingAs($user, 'web');

        $this->withoutExceptionHandling()->get(route('skillSelling.show', ['skillSelling' => 1234]));
    }

    public function test_show_forbidden()
    {
        $this->actingAsRegularUser();

        $skill_selling = SkillSelling::factory()->create();

        $response = $this->get(route('skillSelling.show', ['skillSelling' => $skill_selling->id]));

        $response->assertForbidden();
    }

    public function test_update(): void
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        //create product
        $product = Product::factory()->create();

        // Create a skillselling
        $skillSellingData = SkillSelling::factory()->create([
            'category' => 'Product',
            'link' => 'www.github.com',
            'product_id' => $product->id,
        ]);

        // Generate new data for updating the faq
        $newSkillSellingData = [
            'link' => 'https://www.github.com',
        ];

        // send a PUT request to update the user
        $response = $this->put(route('skillSelling.update', ['skillSelling' => $skillSellingData->id]), $newSkillSellingData);

        // Assert that the request was successful (status code 200)
        $response->assertStatus(200);

        // Assert that the faq was updated with the new data
        $this->assertDatabaseHas('skill_sellings', [
            'link' => $newSkillSellingData['link'],
        ]);
    }

    public function test_update_with_invalid_data()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        $skillSelling = SkillSelling::factory()->create();

        $data = [
            'link' => 'not-a-url',
        ];

        $response = $this->put(route('skillSelling.update', ['skillSelling' => $skillSelling->id]), $data);

        $response->assertStatus(422);
    }

    public function testCategories()
    {
        $response = $this->getJson('/api/skillSellings/categories');

        $response->assertStatus(200);

        $this->assertCount(count(SkillSellingCategory::cases()), $response->json('data'));
    }
}
