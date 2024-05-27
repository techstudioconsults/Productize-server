<?php

namespace Tests\Feature\v1\api;

use App\Enums\ProductStatusEnum;
use App\Enums\ProductTagsEnum;
use App\Events\ProductCreated;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Listeners\SendProductCreatedMail;
use App\Models\Customer;
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
        $total_amount = 100;
        $total_products = 10;
        $total_sales = 20;
        $total_customers = 20;
        $total_revenues = $total_amount * 20;
        $new_orders = 20;
        $new_orders_revenue = $total_amount * 20;
        $views = 1;

        // Create a user
        $user = User::factory()->create([
            'account_type' => 'free_trial'
        ]);

        Product::factory()
            ->count($total_products)
            ->has(
                Order::factory()
                    ->count(2)
                    ->state([
                        'total_amount' => 100
                    ])
                    ->has(Customer::factory()->state([
                        'merchant_id' => $user->id
                    ]))
            )
            ->create([
                'user_id' => $user->id,
            ]);

        // Make a GET request to the analytics endpoint
        $response = $this->actingAs($user, 'web')->get(route('product.analytics'));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(['data' => [
            'total_products',
            'total_sales',
            'total_customers',
            'total_revenues',
            'new_orders',
            'new_orders_revenue',
            'views'
        ]]);

        $response->assertJson(['data' => [
            'total_products' => $total_products,
            'total_sales' => $total_sales,
            'total_customers' => $total_customers,
            'total_revenues' => $total_revenues,
            'new_orders' => $new_orders,
            'new_orders_revenue' => $new_orders_revenue,
            'views' => $views
        ]]);
    }

    public function test_analytics_with_filter(): void
    {
        $total_amount = 100;
        $total_products = 10;
        $total_sales = 20;
        $total_customers = 20;
        $total_revenues = $total_amount * 20;
        $new_orders = 0;
        $new_orders_revenue = 0;
        $views = 1;

        $start_date = Carbon::create(2024, 1, 1, 0);
        $end_date = Carbon::create(2024, 3, 20, 0);

        $within_range_date = Carbon::create(2024, 2, 20, 0);
        $out_of_range_date = Carbon::create(2024, 5, 20, 0);

        // Create a user
        $user = User::factory()->create([
            'account_type' => 'free_trial'
        ]);

        Product::factory()
            ->count($total_products)
            ->has(
                Order::factory()
                    ->count(2)
                    ->state([
                        'total_amount' => $total_amount
                    ])
                    ->sequence(
                        ['created_at' => $within_range_date],
                        ['created_at' => $out_of_range_date],
                    )
                    ->has(
                        Customer::factory()
                            ->state([
                                'merchant_id' => $user->id
                            ])
                            ->sequence(
                                ['created_at' => $within_range_date],
                                ['created_at' => $out_of_range_date],
                            )
                    )
            )
            ->sequence(
                ['created_at' => $within_range_date],
                ['created_at' => $out_of_range_date],
            )
            ->create([
                'user_id' => $user->id,
            ]);

        $filter = [
            'start_date' => $start_date->toDateString(),
            'end_date' => $end_date->toDateString()
        ];

        // Make a GET request to the analytics endpoint
        $response = $this->actingAs($user, 'web')->get(route('product.analytics', $filter));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(['data' => [
            'total_products',
            'total_sales',
            'total_customers',
            'total_revenues',
            'new_orders',
            'new_orders_revenue',
            'views'
        ]]);

        $response->assertJson(['data' => [
            'total_products' => $total_products / 2, // Half of the total products will be within range
            'total_sales' => $total_sales / 2,
            'total_customers' => $total_customers / 2,
            'total_revenues' => $total_revenues / 2,
            'new_orders' => $new_orders,
            'new_orders_revenue' => $new_orders_revenue,
            'views' => $views
        ]]);
    }

    public function test_analytics_unauthenticated(): void
    {
        $this->expectException(UnAuthorizedException::class);

        // Define the start date and end date
        $startDate = '2024-01-01';
        $endDate = '2024-12-31';

        // When
        $this->withoutExceptionHandling()->getJson(route('product.analytics', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]));
    }

    public function test_records(): void
    {
        // create user
        $user = User::factory()->create();
        $this->actingAs($user);

        Product::factory(3)->create(['user_id' => $user->id]);

        $request = [
            'status' =>  ProductStatusEnum::Published->value,
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ];

        // act
        $response = $this->actingAs($user, 'web')->withoutExceptionHandling()->get(route('product.record'), $request);

        // assert
        $response->assertStatus(200);
        $response->assertHeader('Content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', "attachment; filename=products_" . now()->format('d_F_Y') . ".csv");
    }

    public function test_records_unauthenticated(): void
    {
        // We expect that an UnAuthorizedException will be thrown
        $this->expectException(UnAuthorizedException::class);

        // Create a user and some products
        $user = User::factory()->create();
        Product::factory(3)->create(['user_id' => $user->id]);

        // Prepare the request parameters
        $request = [
            'status' => 'published',
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'format' => 'csv'
        ];

        // Send a request to the 'product.record' route, expecting an UnAuthorizedException to be thrown
        $this->withoutExceptionHandling()->get(route('product.record'), $request);
    }

    public function test_togglepublish(): void
    {
        // Create user
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Draft->value
        ]);

        // Act
        $response = $this->actingAs($user, 'web')->patch(route('product.publish', $product->id));

        // Then
        $response->assertStatus(200);

        $product->refresh();

        // Assert
        $this->assertEquals(ProductStatusEnum::Published->value, $product->status);
    }

    public function test_toggle_publish_when_already_published()
    {
        // Create user
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Published->value
        ]);

        // Act
        $response = $this->actingAs($user, 'web')->patch(route('product.publish', $product->id));

        // Assert
        $response->assertStatus(200);

        $product->refresh();
        $this->assertEquals(ProductStatusEnum::Draft->value, $product->status);
    }


    public function test_togglepublish_unauthenticated(): void
    {
        // We expect that an UnAuthorizedException will be thrown
        $this->expectException(UnAuthorizedException::class);

        // create a user
        $user = User::factory()->create();

        // Create a product
        $product = Product::factory()->create(['user_id' => $user->id]);

        // Send a request to the 'product.publish' route, expecting an UnAuthorizedException to be thrown
        $this->withoutExceptionHandling()->patch(route('product.publish', $product->id));
    }

    public function test_togglepublish_deleted_product_throws_400(): void
    {
        $this->expectException(BadRequestException::class);

        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a product and soft delete it
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Draft->value
        ]);
        $product->delete(); // Soft delete the product

        // Act
        $response = $this->withoutExceptionHandling()->actingAs($user, 'web')->patch(route('product.publish', ['product' => $product->id]));

        // Assert
        $response->assertStatus(400);
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

    public function test_delete(): void
    {
        // create user
        $user = User::factory()->create();

        $this->actingAs($user);

        $product = Product::factory()->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Draft->value
        ]);

        // Act
        $response = $this->delete(route('product.delete', [$product->id]));

        // Assert
        $response->assertStatus(200);


        // Assert that the product status is 'draft'
        $this->assertEquals('draft', $product->fresh()->status);

        // Assert that the product is soft deleted
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_delete_unauthenticated(): void
    {
        // We expect that an UnAuthorizedException will be thrown
        $this->expectException(UnAuthorizedException::class);

        // create user
        $user = User::factory()->create();
        // Create a product
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Draft->value
        ]);

        // Act
        $response = $this->withoutExceptionHandling()->delete(route('product.delete', ['product' => $product->id]));

        // Assert
        $response->assertStatus(401); // Expecting Unauthorized status code

        // Assert that the product still exists
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_delete_not_for_user_return_403(): void
    {
        // We expect that a fprbidden request will be thrown
        $this->expectException(ForbiddenException::class);

        // create user
        $user = User::factory()->create();
        $this->actingAs($user);
        $user2 = User::factory()->create();
        // create product
        $product = Product::factory()->create([
            'user_id' => $user2->id, // not found
            'status' => ProductStatusEnum::Draft->value
        ]);

        // delete user
        $user->delete();


        // Act
        $response = $this->withoutExceptionHandling()->delete(route('product.delete', ['product' => $product->id]));

        // Assert
        $response->assertStatus(403);

        // Assert that the product still exists
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_restore(): void
    {
        // Create user
        $user = User::factory()->create();

        $this->actingAs($user);

        // Create Product
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Draft->value
        ]);

        $product->delete();

        $this->assertSoftDeleted($product);

        // Act
        $response = $this->withoutExceptionHandling()->get(route('product.restore', ['product' => $product->id]));

        // Assert
        $response->assertStatus(200);
        $this->assertNotSoftDeleted($product);
    }

    public function test_restore_unauthenticated(): void
    {
        // We expect that an UnAuthorizedException will be thrown
        $this->expectException(UnAuthorizedException::class);

        // Create User
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Draft->value
        ]);

        $response = $this->withoutExceptionHandling()->get(route('product.restore', ['product' => $product->id]));

        // Assert
        $response->assertStatus(401); // Expecting Unauthorized status code

        // Assert that the product still exists
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_restore_not_found(): void
    {
        // We expect that a ModelNotFoundException will be thrown
        $this->expectException(ModelNotFoundException::class);

        // Create a user and act as this user
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        // Attempt to restore a non-existent product
        $this->withoutExceptionHandling()->get(route('product.restore', ['product' => '234553']));
    }

    public function test_restore_not_for_user(): void
    {
        // We expect that a forbidden request will be thrown
        $this->expectException(ForbiddenException::class);

        // Create two users
        $user = User::factory()->create();

        $user2 = User::factory()->create();

        $this->actingAs($user);

        // Create a product that belongs to the second user and soft delete it
        $product = Product::factory()->create([
            'user_id' => $user2->id,
            'status' => ProductStatusEnum::Draft->value
        ]);

        $product->delete();

        // Assert that the product is soft deleted
        $this->assertSoftDeleted($product);

        // Act: Attempt to restore the product as the first user
        $response = $this->withoutExceptionHandling()->get(route('product.restore', ['product' => $product->id]));

        // Assert that the response status is 403
        $response->assertStatus(403);

        // Assert that the product is still soft deleted
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_forcedelete(): void
    {
        // Create a user and act as this user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create product
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Draft->value
        ]);

        // Soft delete the product
        $product->delete();

        // Ensure the product is soft deleted
        $this->assertSoftDeleted($product);

        $response = $this->delete(route('product.delete.force', [$product->id]));


        // Assert: Check the response status
        $response->assertStatus(200);
        $response->assertSee('product is permanently deleted');
    }

    public function test_forcedelete_unauthenticated(): void
    {
        // We expect that an UnAuthorizedException will be thrown
        $this->expectException(UnAuthorizedException::class);

        // Create User
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Draft->value
        ]);

        // Soft delete the product
        $product->delete();

        // Ensure the product is soft deleted
        $this->assertSoftDeleted($product);

        $response = $this->withoutExceptionHandling()->delete(route('product.delete.force', [$product->id]));

        // Assert
        $response->assertStatus(401); // Expecting Unauthorized status code

        // Assert that the product still exists
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_forcedelete_not_found(): void
    {
        // We expect that a ModelNotFoundException will be thrown
        $this->expectException(ModelNotFoundException::class);

        // Create a user and act as this user
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        // Attempt to force delete a non-existent product
        $this->withoutExceptionHandling()->delete(route('product.delete.force', ['product' => '234553']));
    }

    public function test_forcedelete_not_for_user(): void
    {
        // We expect that a forbidden request will be thrown
        $this->expectException(ForbiddenException::class);

        // Create two users
        $user = User::factory()->create();
        $this->actingAs($user);
        $user2 = User::factory()->create();

        // Create a product that belongs to the second user
        $product = Product::factory()->create([
            'user_id' => $user2->id, // Product does not belong to $user
            'status' => ProductStatusEnum::Draft->value
        ]);

        // Act: Attempt to force delete the product as the first user
        $response = $this->withoutExceptionHandling()->delete(route('product.delete.force', ['product' => $product->id]));

        // Assert that the response status is 403
        $response->assertStatus(403);

        // Assert that the product still exists in the database
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_downloads(): void
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create another user who will be the product publisher
        $publisher = User::factory()->create();

        // Create products and associate them with the publisher
        $products = Product::factory()->count(3)->create([
            'user_id' => $publisher->id,
            'status' => ProductStatusEnum::Published->value
        ]);

        // Create orders for the authenticated user
        foreach ($products as $product) {
            Order::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        // Act
        $response = $this->get(route('product.download'));

        // Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
                'data',
                'thumbnail',
                'slug',
                'publisher',
                'price',
            ],
        ]);

        foreach ($products as $product) {
            $response->assertJsonFragment([
                'id' => $product->id,
                'title' => $product->title,
                'data' => $product->data,
                'thumbnail' => $product->thumbnail,
                'slug' => $product->slug,
                'publisher' => $publisher->full_name,
                'price' => $product->price,
            ]);
        }
    }

    public function test_downloads_unauthenticated(): void
    {
        // We expect that an UnAuthorizedException will be thrown
        $this->expectException(UnAuthorizedException::class);

        // Act
        $response = $this->withoutExceptionHandling()->get(route('product.download'));

        // Assert that the response status is 401 Unauthorized
        $response->assertStatus(401);
    }

    public function test_tags(): void
    {
        $expectedTags = array_map(function ($tags) {
            return $tags->value;;
        }, ProductTagsEnum::cases());

        // Act
        $response = $this->get(route('product.tags'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => $expectedTags
        ]);
    }
}
