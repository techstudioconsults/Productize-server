<?php

namespace Tests\Feature\v1\api;

use App\Events\ProductCreated;
use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Listeners\SendProductCreatedMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Storage;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private ProductRepository $productRepository;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->productRepository = app(ProductRepository::class);

        // Create user with a free trial account, else test fails - check UserFactory.php
        $this->user = User::factory()->create(['account_type' => 'free_trial']);

        $this->productRepository->seed();
    }

    public function test_index(): void
    {
        $response = $this->get(route('product.index'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->has('meta')
                ->has('links')
                ->has('data', 15)
                ->has(
                    'data.0',
                    fn (AssertableJson $json) =>
                    $json
                        ->hasAll([
                            'title', 'thumbnail', 'price',
                            'publisher', 'slug', 'highlights', 'product_type', 'cover_photos',
                            'tags', 'description', 'status'
                        ])
                        ->where('status', 'published')
                        ->missing('id')
                        ->etc()
                )
        );
    }

    public function test_user(): void
    {
        // Create user with a free trial account, else test fails - check UserFactory.php
        // $user = User::factory()->create(['account_type' => 'free_trial']);

        $products = Product::factory(2)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'web')->get(route('product.user'));

        // Convert the products to ProductResource
        $expected_json = ProductResource::collection($products)->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);
    }

    public function test_user_with_date_range(): void
    {
        // Define the date range
        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 3, 20, 0);

        // Create user with a free trial account, else test fails - check UserFactory.php
        $user = User::factory()->create(['account_type' => 'free_trial']);

        // Create products within range
        $products = Product::factory(1)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create(2024, 3, 15, 0)->toDateTimeString(),
        ]);

        // Create products outside range
        Product::factory(5)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        $response = $this->actingAs($user, 'web')->get(route('product.user', [
            'start_date' => $start_date,
            'end_date' => $end_date
        ]));

        // Convert the products to ProductResource
        $expected_json = ProductResource::collection($products)->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);
    }

    public function test_user_unauthenticated(): void
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()->get(route('product.user'));
    }

    public function test_show(): void
    {
        // Storage::fake('s3');

        // // Create a product
        // $product = Product::factory()->create(['user_id' => $this->user->id]);

        // $response = $this->actingAs($this->user, 'web')->get(route('product.show', [
        //     'product' => $product->id
        // ]));

        // $response->assertOk();
    }

    public function test_show_unauthenticated(): void
    {
    }

    public function test_show_product_not_found_return_404(): void
    {
    }

    public function test_findbyslug(): void
    {
    }

    public function test_findbyslug_unauthenticated(): void
    {
    }

    public function test_findbyslug_slug_not_found_should_return_404(): void
    {
    }

    public function test_findbyslug_published_product_should_return_400_bad_request(): void
    {
    }

    public function test_store_first_product_should_update_user_first_product_created(): void
    {
        // Fake s3 storage
        Storage::fake('s3');

        // Fake event for product created event
        Event::fake([ProductCreated::class]);

        // Mocking payload
        $payload = [
            'title' => 'title',
            'price' => 2,
            'product_type' => 'digital_product',
            'thumbnail' => UploadedFile::fake()->image('avatar.jpg'),
            'description' => 'description',
            'data' => [UploadedFile::fake()->create('data1.pdf')],
            'cover_photos' => [UploadedFile::fake()->image('cover1.jpg')],
            'highlights' => ['highlight1', 'highlight2'],
            'tags' => ['tag1', 'tag2'],
            'stock_count' => true,
            'choose_quantity' => false,
            'show_sales_count' => true,
        ];

        // Asserting that the user's first product created at property is null before creating the product
        $this->assertNull($this->user->first_product_created_at);

        $response = $this
            ->actingAs($this->user, 'web')
            ->withoutExceptionHandling()
            ->post(route('product.store'), $payload);

        // Asserting that the request was successful
        $response->assertCreated();

        // Assert files are saved in the disk storage
        Storage::disk('s3')->assertExists('products-thumbnail/avatar.jpg');
        Storage::disk('s3')->assertExists('digital-products/data1.pdf');

        // Assert the event was dispatched
        Event::assertDispatched(ProductCreated::class);

        // Assert SendProductCreatedMail listener is listening
        Event::assertListening(ProductCreated::class, SendProductCreatedMail::class);

        // Asserting that the user's first product created at property is now set
        $this->user->refresh();
        $this->assertNotNull($this->user->first_product_created_at);
    }

    public function test_store_not_first_product(): void
    {
        // Create products
        Product::factory(5)->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);

        // set first product created time
        $this->user->first_product_created_at = Carbon::now();

        $expected_created_time = $this->user->first_product_created_at;

        // Fake s3 storage
        Storage::fake('s3');

        // Fake event for product created event
        Event::fake([ProductCreated::class]);

        // Mocking payload
        $payload = [
            'title' => 'title',
            'price' => 2,
            'product_type' => 'digital_product',
            'thumbnail' => UploadedFile::fake()->image('avatar.jpg'),
            'description' => 'description',
            'data' => [UploadedFile::fake()->create('data1.pdf')],
            'cover_photos' => [UploadedFile::fake()->image('cover1.jpg')],
            'highlights' => ['highlight1', 'highlight2'],
            'tags' => ['tag1', 'tag2'],
            'stock_count' => true,
            'choose_quantity' => false,
            'show_sales_count' => true,
        ];

        // Asserting that this is not the user's first product.
        $this->assertNotNull($this->user->first_product_created_at);

        $response = $this
            ->actingAs($this->user, 'web')
            ->withoutExceptionHandling()
            ->post(route('product.store'), $payload);

        // Asserting that the request was successful
        $response->assertCreated();

        // Assert files are saved in the disk storage
        Storage::disk('s3')->assertExists('products-thumbnail/avatar.jpg');
        Storage::disk('s3')->assertExists('digital-products/data1.pdf');

        // Assert the event was dispatched
        Event::assertDispatched(ProductCreated::class);

        // Assert SendProductCreatedMail listener is listening
        Event::assertListening(ProductCreated::class, SendProductCreatedMail::class);

        // Asserting that the user's first product created property is unchanged. model listener method was not called
        $this->user->refresh();
        $this->assertEquals($expected_created_time, $this->user->first_product_created_at);
    }

    public function test_store_unauthenticated(): void
    {
        $this->expectException(UnAuthorizedException::class);

        // Mocking payload
        $payload = [
            'title' => 'title',
            'price' => 2,
            'product_type' => 'digital_product',
            'thumbnail' => UploadedFile::fake()->image('avatar.jpg'),
            'description' => 'description',
            'data' => [UploadedFile::fake()->create('data1.pdf')],
            'cover_photos' => [UploadedFile::fake()->image('cover1.jpg')],
            'highlights' => ['highlight1', 'highlight2'],
            'tags' => ['tag1', 'tag2'],
            'stock_count' => true,
            'choose_quantity' => false,
            'show_sales_count' => true,
        ];

        $this
            ->withoutExceptionHandling()
            ->post(route('product.store'), $payload);
    }

    public function test_store_invalid_payload_throw_422()
    {
        $this->expectException(UnprocessableException::class);

        // Mocking payload
        $payload = [ // fail to send a title
            'price' => 2,
            'product_type' => 'digital_product',
            'thumbnail' => UploadedFile::fake()->create('avatar.pdf'), // send a pdf instead of an image
            'description' => 'description',
            'data' => [UploadedFile::fake()->create('data1.pdf')],
            'cover_photos' => [UploadedFile::fake()->image('cover1.jpg')],
            'highlights' => ['highlight1', 'highlight2'],
            'tags' => ['tag1', 'tag2'],
            'stock_count' => true,
            'choose_quantity' => false,
            'show_sales_count' => true,
        ];

        $this
            ->actingAs($this->user, 'web')
            ->withoutExceptionHandling()
            ->post(route('product.store'), $payload);
    }

    public function test_analytics(): void
    {
    }

    public function test_analytics_unauthenticated(): void
    {
    }

    public function test_downloadList(): void
    {
    }

    public function test_downloadlist_unauthenticated(): void
    {
    }

    public function test_togglepublish(): void
    {
    }

    public function test_togglepublish_unauthenticated(): void
    {
    }

    public function test_togglepublish_deleted_product_throws_400(): void
    {
    }

    public function test_update(): void
    {
    }

    public function test_update_unauthenticated(): void
    {
    }

    public function test_update_product_not_found(): void
    {
    }

    public function test_update_not_user_product(): void
    {
    }

    public function test_update_invalid_payload(): void
    {
    }

    public function test_top_products(): void
    {
        $products = Product::factory()
            ->count(5)
            ->has(
                Order::factory()
                    ->count(3)
                    ->state(function () {
                        return ['quantity' => 2];
                    })
            )
            ->create(['user_id' => User::factory()->create(), 'price' => 200000]);

        $response = $this->get(route('product.top-products'));

        $expected_json = ProductCollection::make($products)->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);
    }

    public function test_top_products_no_order(): void
    {
        $response = $this->get(route('product.top-products'));

        $expected_json = ProductCollection::make([])->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);
    }
}
