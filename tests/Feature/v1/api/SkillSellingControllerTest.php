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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillSellingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_storeSkillSelling(): void
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        //create product
        $product = Product::factory()->create();

        // Generate new data for creating the skillselling
        $skillsellingData = [
            'level' => 'high',
            'availability' => 'yes',
            'category' => 'Product',
            'link' => 'www.github.com',
            'product_id' => $product->id
        ];

        // Send a POST request to store the skillselling
        $response = $this->post('api/skillSellings/', $skillsellingData);

        // Assert that the request was successful (status code 201)
        $response->assertStatus(201);

        // Assert that the skillselling was stored in the database with the provided data
        $this->assertDatabaseHas('skill_sellings', [
            'level' => $skillsellingData['level'],
            'availability' => $skillsellingData['availability'],
            'product_id' => $skillsellingData['product_id'],
            'link' => $skillsellingData['link'],
        ]);
    }

    public function test_updateSkillSelling(): void
    {

        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        //create product
        $product = Product::factory()->create();

        // Create a skillselling
        $skillSellingData = SkillSelling::factory()->create([
            'level' => 'high',
            'availability' => 'yes',
            'category' => 'Product',
            'link' => 'www.github.com',
            'product_id' => $product->id
        ]);

        // Generate new data for updating the faq
        $newSkillSellingData = [
            'level' => 'medium',
            'availability' => 'no',
            'link' => 'www.github.com',
        ];

        // send a PUT request to update the user
        $response = $this->put('api/skillSellings/' . $skillSellingData->id, $newSkillSellingData);

        // Assert that the request was successful (status code 200)
        $response->assertStatus(200);

        // Assert that the faq was updated with the new data
        $this->assertDatabaseHas('skill_sellings', [
            'level' => $newSkillSellingData['level'],
            'availability' => $newSkillSellingData['availability'],
            'link' => $newSkillSellingData['link'],
        ]);
    }

    public function test_show_unauthenticated()
    {
        //create product
        $product = Product::factory()->create();

        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()
            ->get('api/skillSellings/products/' . $product->id);
    }

    public function test_show(): void
    {
        // Create a user
        $user = User::factory()->create();

        //create product
        $product = Product::factory()->create();

        // Create a skillselling
        $skillSelling = SkillSelling::factory()->create([
            'level' => 'high',
            'availability' => 'yes',
            'category' => 'Product',
            'link' => 'www.github.com',
            'product_id' => $product->id
        ]);


        // Invoke the show method
        $response =  $this->actingAs($user, 'web')->get('api/skillSellings/products/' . $product->id);

        $expected_json = SkillSellingResource::make($skillSelling)->response()->getData(true);

        $response->assertOk()->assertJson($expected_json, true);
    }

    public function test_show_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();

        $this->actingAs($user, 'web')->withoutExceptionHandling()->get(route('skillSelling.show', ['product' => '1234']));
    }


    public function testStoreWithInvalidData()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'category' => 'Invalid Category',
            'level' => '',
            'availability' => '',
            'link' => 'not-a-url',
            'product_id' => 'non-existent-id',
        ];

        $response = $this->postJson('/api/skillSellings', $data);

        $response->assertStatus(422);
    }

    public function testUpdateWithInvalidData()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        $skillSelling = SkillSelling::factory()->create();

        $data = [
            'level' => '',
            'availability' => '',
            'link' => 'not-a-url',
        ];

        $response = $this->putJson("/api/skillSellings/{$skillSelling->id}", $data);

        $response->assertStatus(422);
    }

    public function testCategories()
    {
        $response = $this->getJson('/api/skillSellings/categories');

        $response->assertStatus(200);

        $this->assertCount(count(SkillSellingCategory::cases()), $response->json('data'));
    }
}
