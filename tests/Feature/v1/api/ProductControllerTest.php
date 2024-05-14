<?php

namespace Tests\Feature\v1\api;

use App\Enums\ProductStatusEnum;
use App\Events\ProductCreated;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
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
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        $data = [
            "https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/3d_collection_showcase-20210110-0001.jpg",
            "https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/3d_collection_showcase-20210110-0001.pdf",
        ];

        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'data' => $data
        ]);

        // Mock product repository
        $mock = $this->partialMock(ProductRepository::class);

        // Mock the getFileMetaData method
        $mock->shouldReceive('getFileMetaData')->andReturnUsing(function ($file_path) {

            // Mock metadata based on the file path
            if ($file_path === "/products-cover-photos/3d_collection_showcase-20210110-0001.jpg") {
                return ['size' => '10MB', 'mime_type' => 'image/jpeg'];
            } elseif ($file_path === "/products-cover-photos/3d_collection_showcase-20210110-0001.pdf") {
                return ['size' => '5MB', 'mime_type' => 'application/pdf'];
            } else {
                return null; // Return null for unknown file paths
            }
        });

        // Invoke the show method
        $response = $this->actingAs($this->user, 'web')->get(route('product.show', [
            'product' => $product->id
        ]));

        // Assert response status
        $response
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('no_of_resources', 2)
                    ->where('id', $product->id)
                    ->etc()
            );
    }

    public function test_show_unauthenticated(): void
    {
        $this->expectException(UnAuthorizedException::class);

        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Make a request without token
        $this->withoutExceptionHandling()->get(route('product.show', [
            'product' => $product->id
        ]));
    }

    public function test_show_product_not_found_return_404(): void
    {
        $this->expectException(ModelNotFoundException::class);

        // Make a request without token
        $this
            ->withoutExceptionHandling()
            ->actingAs($this->user, 'web')
            ->get(route('product.show', [
                'product' => "12345"
            ]));
    }

    public function test_show_product_forbidden_for_wrong_user_return_403()
    {
        $this->expectException(ForbiddenException::class);

        $forbidden_user = User::factory()->create();

        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this
            ->withoutExceptionHandling()
            ->actingAs($forbidden_user, 'web')
            ->get(route('product.show', [
                'product' => $product->id
            ]));
    }

    public function test_slug(): void
    {
        $data = [
            "https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/3d_collection_showcase-20210110-0001.jpg",
            "https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/3d_collection_showcase-20210110-0001.pdf",
        ];

        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value, // only published products can be retrieved by their slugs
            'data' => $data
        ]);

        // Mock product repository
        $mock = $this->partialMock(ProductRepository::class);

        // Mock the getFileMetaData method
        $mock->shouldReceive('getFileMetaData')->andReturnUsing(function ($file_path) {

            // Mock metadata based on the file path
            if ($file_path === "/products-cover-photos/3d_collection_showcase-20210110-0001.jpg") {
                return ['size' => '10MB', 'mime_type' => 'image/jpeg'];
            } elseif ($file_path === "/products-cover-photos/3d_collection_showcase-20210110-0001.pdf") {
                return ['size' => '5MB', 'mime_type' => 'application/pdf'];
            } else {
                return null; // Return null for unknown file paths
            }
        });

        // Invoke the slug method
        $response = $this
            ->withoutExceptionHandling()
            ->actingAs($this->user, 'web')
            ->get(route('product.slug', [
                'product' => $product->slug,
                'slug' => "1234"
            ]));

        // Assert response status
        $response
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('no_of_resources', 2)
                    ->where('slug', $product->slug)
                    ->etc()
            );
    }

    public function test_slug_unauthenticated_have_access(): void
    {
        $data = [
            "https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/3d_collection_showcase-20210110-0001.jpg",
            "https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/3d_collection_showcase-20210110-0001.pdf",
        ];

        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value, // only published products can be retrieved by their slugs
            'data' => $data
        ]);

        // Mock product repository
        $mock = $this->partialMock(ProductRepository::class);

        // Mock the getFileMetaData method
        $mock->shouldReceive('getFileMetaData')->andReturnUsing(function ($file_path) {

            // Mock metadata based on the file path
            if ($file_path === "/products-cover-photos/3d_collection_showcase-20210110-0001.jpg") {
                return ['size' => '10MB', 'mime_type' => 'image/jpeg'];
            } elseif ($file_path === "/products-cover-photos/3d_collection_showcase-20210110-0001.pdf") {
                return ['size' => '5MB', 'mime_type' => 'application/pdf'];
            } else {
                return null; // Return null for unknown file paths
            }
        });

        // Invoke the slug method
        $response = $this
            ->withoutExceptionHandling()
            ->get(route('product.slug', [
                'product' => $product->slug,
                'slug' => "1234"
            ]));

        // Assert response status
        $response
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('no_of_resources', 2)
                    ->where('slug', $product->slug)
                    ->etc()
            );
    }

    public function test_slug_slug_not_found_should_return_404(): void
    {
        $this->expectException(ModelNotFoundException::class);

        // Make a request without token
        $this
            ->withoutExceptionHandling()
            ->get(route('product.slug', [
                'product' => "12345",
                'slug' => "1234"
            ]));
    }

    public function test_slug_published_product_should_return_400_bad_request(): void
    {
        $this->expectException(BadRequestException::class);

        $data = [
            "https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/3d_collection_showcase-20210110-0001.jpg",
            "https://productize.nyc3.cdn.digitaloceanspaces.com/products-cover-photos/3d_collection_showcase-20210110-0001.pdf",
        ];


        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'data' => $data
        ]);

        // Invoke the slug method
        $this
            ->withoutExceptionHandling()
            ->actingAs($this->user, 'web')
            ->get(route('product.slug', [
                'product' => $product->slug,
                'slug' => "1234"
            ]));
    }

    public function test_store_first_product_should_update_user_first_product_created(): void
    {
        // Fake spaces storage
        Storage::fake('spaces');

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
        Storage::disk('spaces')->assertExists('products-thumbnail/avatar.jpg');
        Storage::disk('spaces')->assertExists('digital-products/data1.pdf');

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

        // Fake spaces storage
        Storage::fake('spaces');

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
        $response->assertStatus(201);

        Storage::disk('spaces')->assertExists('products-thumbnail/avatar.jpg');
        Storage::disk('spaces')->assertExists('digital-products/data1.pdf');
        Event::assertDispatched(ProductCreated::class);
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

    public function test_update(): void
    {
        // Fake spaces storage
        Storage::fake('spaces');

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Mocking payload
        $payload = [
            'title' => 'title updated',
            'price' => 3,
            'thumbnail' => UploadedFile::fake()->image('avatar_update.jpg'),
            'description' => 'description',
            'data' => [UploadedFile::fake()->create('data_update.pdf')],
            'cover_photos' => [UploadedFile::fake()->image('cover1_update.jpg')],
        ];

        $response = $this
            ->withoutExceptionHandling()
            ->actingAs($this->user, 'web')
            ->put(route('product.update', [
                'product' => $product->id
            ]), $payload);

        // Asserting that the request was successful
        $response->assertOk()->assertJson(
            fn (AssertableJson $json) =>
            $json->where('id', $product->id)
                ->where('title', 'title updated')
                ->where('price', 3)
                ->where('thumbnail', config('filesystems.disks.spaces.cdn_endpoint') . '/' . ProductRepository::THUMBNAIL_PATH . '/avatar_update.jpg')
                ->etc()
        );

        Storage::disk('spaces')->assertExists(ProductRepository::THUMBNAIL_PATH . '/avatar_update.jpg');
        Storage::disk('spaces')->assertExists(ProductRepository::PRODUCT_DATA_PATH . '/data_update.pdf');
        Storage::disk('spaces')->assertExists(ProductRepository::COVER_PHOTOS_PATH . '/cover1_update.jpg');
    }

    public function test_update_unauthenticated(): void
    {
        $this->expectException(UnAuthorizedException::class);

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Mocking payload
        $payload = [
            'title' => 'title updated',
            'price' => 3,
            'thumbnail' => UploadedFile::fake()->image('avatar_update.jpg'),
            'description' => 'description',
            'data' => [UploadedFile::fake()->create('data_update.pdf')],
            'cover_photos' => [UploadedFile::fake()->image('cover1_update.jpg')],
        ];

        $this
            ->withoutExceptionHandling()
            ->put(route('product.update', [
                'product' => $product->id
            ]), $payload);
    }

    public function test_update_product_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        // Mocking payload
        $payload = [
            'title' => 'title updated',
            'price' => 3,
            'description' => 'description',
        ];

        $this
            ->withoutExceptionHandling()
            ->actingAs($this->user, 'web')
            ->put(route('product.update', [
                'product' => '12345'
            ]), $payload);
    }

    public function test_update_not_user_product(): void
    {
        $this->expectException(ForbiddenException::class);

        $forbidden_user = User::factory()->create();

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Mocking payload
        $payload = [
            'title' => 'title updated',
            'price' => 3,
            'description' => 'description',
        ];

        $this
            ->withoutExceptionHandling()
            ->actingAs($forbidden_user, 'web')
            ->put(route('product.update', [
                'product' => $product->id
            ]), $payload);
    }

    public function test_update_invalid_payload(): void
    {
        $this->expectException(UnprocessableException::class);

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Mocking payload
        $payload = [
            'title' => 1, // Invalid payload
            'price' => 3,
            'description' => 'description',
        ];

        $this
            ->withoutExceptionHandling()
            ->actingAs($this->user, 'web')
            ->put(route('product.update', [
                'product' => $product->id
            ]), $payload);
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
