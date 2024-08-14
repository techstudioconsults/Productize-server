<?php

namespace Tests\Feature\v1\api;

use App\Enums\ProductStatusEnum;
use App\Enums\ProductTagsEnum;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Http\Resources\ExternalProductResource;
use App\Http\Resources\ProductResource;
use App\Mail\BestSellerCongratulations;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use App\Traits\SanctumAuthentication;
use Carbon\Carbon;
use Database\Seeders\ProductSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\Fluent\AssertableJson;
use Mail;
use Storage;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;
    use SanctumAuthentication, WithFaker;

    private ProductRepository $productRepository;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->productRepository = app(ProductRepository::class);

        // Create user with a free trial account, else test fails - check UserFactory.php
        $this->user = User::factory()->create(['account_type' => 'free_trial']);
    }

    public function test_index_with_date_filters()
    {
        $this->actingAsSuperAdmin();

        $expected_count = 4; // Ensure it matches the paginated count in controller

        // Create products for testing
        $products = Product::factory()->count($expected_count)
            ->create([
                'user_id' => User::factory()->create()->id,
                'created_at' => now()->subDays(10),
            ]);

        // create products out of date range
        Product::factory()->count($expected_count)
            ->create([
                'user_id' => User::factory()->create()->id,
                'created_at' => now()->subDays(20),
            ]);

        // Convert the products to ProductResource
        $expected_json = ProductResource::collection($products)->response()->getData(true);

        // Call the index endpoint with date filters
        $response = $this->withoutExceptionHandling()->get(route('product.index', [
            'start_date' => now()->subDays(15)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertOk()->assertJson($expected_json, true);

        // Assert that the response contains the correct number of products
        $response->assertJsonCount($expected_count, 'data');
    }

    public function test_index_with_date_filters_for_admin()
    {
        $this->actingAsAdmin();

        $expected_count = 4; // Ensure it matches the paginated count in controller

        // Create products for testing
        $products = Product::factory()->count($expected_count)
            ->create([
                'user_id' => User::factory()->create()->id,
                'created_at' => now()->subDays(10),
            ]);

        // create products out of date range
        Product::factory()->count($expected_count)
            ->create([
                'user_id' => User::factory()->create()->id,
                'created_at' => now()->subDays(20),
            ]);

        // Convert the products to ProductResource
        $expected_json = ProductResource::collection($products)->response()->getData(true);

        // Call the index endpoint with date filters
        $response = $this->withoutExceptionHandling()->get(route('product.index', [
            'start_date' => now()->subDays(15)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertOk()->assertJson($expected_json, true);

        // Assert that the response contains the correct number of products
        $response->assertJsonCount($expected_count, 'data');
    }

    public function test_index_without_filters()
    {
        $this->actingAsSuperAdmin();

        $expected_count = 4; // Ensure it matches the paginated count in controller

        // Create products for testing
        $products = Product::factory()->count($expected_count)->create();

        // Convert the products to ProductResource
        $expected_json = ProductResource::collection($products)->response()->getData(true);

        // Call the index endpoint without filters
        $response = $this->get(route('product.index'));

        // Assert response is successful
        $response->assertOk()->assertJson($expected_json, true);

        // Assert that the response contains the correct number of products
        $response->assertJsonCount($expected_count, 'data');
    }

    public function test_index_without_filters_for_admin()
    {
        $this->actingAsAdmin();

        $expected_count = 4; // Ensure it matches the paginated count in controller

        // Create products for testing
        $products = Product::factory()->count($expected_count)->create();

        // Convert the products to ProductResource
        $expected_json = ProductResource::collection($products)->response()->getData(true);

        // Call the index endpoint without filters
        $response = $this->get(route('product.index'));

        // Assert response is successful
        $response->assertOk()->assertJson($expected_json, true);

        // Assert that the response contains the correct number of products
        $response->assertJsonCount($expected_count, 'data');
    }

    public function test_index_with_non_super_admin()
    {
        $this->actingAsRegularUser();

        // Call the index endpoint
        $response = $this->get(route('product.index'));

        // Assert forbidden response
        $response->assertForbidden();
    }

    public function test_external(): void
    {
        $users = User::factory(5)->create();

        foreach ($users as $user) {
            // Create 5 products for each user
            Product::factory()
                ->count(5)
                ->state(new Sequence(
                    ['status' => 'published'],
                    ['status' => 'draft'],
                ))
                ->create(['user_id' => $user->id, 'price' => '100000']);
        }

        $response = $this->get(route('product.external'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->has('data', 15)
                ->has(
                    'data.0',
                    fn (AssertableJson $json) => $json
                        ->hasAll([
                            'title', 'thumbnail', 'price',
                            'publisher', 'slug', 'highlights', 'product_type', 'cover_photos',
                            'tags', 'description', 'status',
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
            'end_date' => $end_date,
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
        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $expected_json = ProductResource::make($product)->response()->getData(true);

        // Invoke the show method
        $response = $this->actingAs($this->user, 'web')->get(route('product.show', [
            'product' => $product->id,
        ]));

        $response->assertOk()->assertJson($expected_json, true);
    }

    public function test_show_unauthenticated(): void
    {
        $this->expectException(UnAuthorizedException::class);

        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Make a request without token
        $this->withoutExceptionHandling()->get(route('product.show', [
            'product' => $product->id,
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
                'product' => '12345',
            ]));
    }

    public function test_show_product_forbidden_for_wrong_user_return_403()
    {
        $this->expectException(ForbiddenException::class);

        $forbidden_user = User::factory()->create();

        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this
            ->withoutExceptionHandling()
            ->actingAs($forbidden_user, 'web')
            ->get(route('product.show', [
                'product' => $product->id,
            ]));
    }

    public function test_slug(): void
    {
        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value, // only published products can be retrieved by their slugs
        ]);

        $expected_json = ExternalProductResource::make($product)->response()->getData(true);

        // Invoke the slug method
        $response = $this
            ->withoutExceptionHandling()
            ->actingAs($this->user, 'web')
            ->get(route('product.slug', [
                'product' => $product->slug,
                'slug' => '1234',
            ]));

        // Assert response status
        $response->assertOk()->assertJson($expected_json, true);
    }

    public function test_slug_unauthenticated_have_access(): void
    {
        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value, // only published products can be retrieved by their slugs
        ]);

        $expected_json = ExternalProductResource::make($product)->response()->getData(true);

        // Invoke the slug method
        $response = $this
            ->withoutExceptionHandling()
            ->get(route('product.slug', [
                'product' => $product->slug,
                'slug' => '1234',
            ]));

        // Assert response status
        $response->assertOk()->assertJson($expected_json, true);
    }

    public function test_slug_not_found_should_return_404(): void
    {
        $this->expectException(ModelNotFoundException::class);

        // Make a request without token
        $this
            ->withoutExceptionHandling()
            ->get(route('product.slug', [
                'product' => '12345',
                'slug' => '1234',
            ]));
    }

    public function test_slug_unpublished_product_should_return_400_bad_request(): void
    {
        $this->expectException(BadRequestException::class);

        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Invoke the slug method
        $this
            ->withoutExceptionHandling()
            ->actingAs($this->user, 'web')
            ->get(route('product.slug', [
                'product' => $product->slug,
                'slug' => '1234',
            ]));
    }

    public function test_slug_search_product_should_be_tracked(): void
    {
        $user = User::factory()->create();

        // Create a product
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value, // only published products can be retrieved by their slugs
        ]);

        // Mock product repository
        $mock = $this->partialMock(ProductRepository::class);

        // mock the isSeachedProductMethod
        $mock->shouldReceive('isSearchedProduct')->andReturn(true);

        // Invoke the slug method
        $response = $this
            ->withoutExceptionHandling()
            ->actingAs($user, 'web')
            ->get(route('product.slug', [
                'product' => $product->slug,
                'slug' => '1234',
            ]));

        // Assert response status
        $response
            ->assertOk();

        $this->assertDatabaseHas('product_searches', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_store_first_product_should_update_user_first_product_created(): void
    {
        // Fake spaces storage
        Storage::fake('spaces');

        // Mocking payload
        $payload = [
            'title' => 'title',
            'price' => 2,
            'product_type' => 'digital_product',
            'thumbnail' => UploadedFile::fake()->image('avatar.jpg'),
            'description' => 'description',
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

        // Mocking payload
        $payload = [
            'title' => 'title',
            'price' => 2,
            'product_type' => 'digital_product',
            'thumbnail' => UploadedFile::fake()->image('avatar.jpg'),
            'description' => 'description',
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
            'cover_photos' => [UploadedFile::fake()->image('cover1_update.jpg')],
        ];

        $response = $this
            ->withoutExceptionHandling()
            ->actingAs($this->user, 'web')
            ->put(route('product.update', [
                'product' => $product->id,
            ]), $payload);

        $product->refresh();

        $expected_json = ProductResource::make($product)->response()->getData(true);

        $response->assertOk()->assertJson($expected_json, true);

        Storage::disk('spaces')->assertExists(ProductRepository::THUMBNAIL_PATH.'/avatar_update.jpg');
        Storage::disk('spaces')->assertExists(ProductRepository::COVER_PHOTOS_PATH.'/cover1_update.jpg');
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
                'product' => $product->id,
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
                'product' => '12345',
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
                'product' => $product->id,
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
                'product' => $product->id,
            ]), $payload);
    }

    public function test_analytics(): void
    {
        $total_amount = 100;
        $total_products = 10;
        $total_sales = 123;
        $total_customers = 20;
        $total_revenues = $total_amount * 20;
        $new_orders = 20;
        $new_orders_revenue = $total_amount * 20;
        $views = 1;

        // Create a user
        $user = User::factory()->create([
            'account_type' => 'free_trial',
        ]);

        Product::factory()
            ->count($total_products)
            ->has(
                Order::factory()
                    ->count(2)
                    ->state([
                        'total_amount' => 100,
                    ])
                    ->has(Customer::factory()->state([
                        'merchant_id' => $user->id,
                    ]))
            )
            ->create([
                'user_id' => $user->id,
            ]);

        // Make a GET request to the analytics endpoint
        $response = $this->actingAs($user, 'web')->withoutExceptionHandling()->get(route('product.analytics'));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(['data' => [
            'total_products',
            'total_sales',
            'total_customers',
            'total_revenues',
            'new_orders',
            'new_orders_revenue',
            'views',
        ]]);

        // $response->assertJson(['data' => [
        //     'total_products' => $total_products,
        //     'total_sales' => $total_sales,
        //     'total_customers' => $total_customers,
        //     'total_revenues' => $total_revenues,
        //     'new_orders' => $new_orders,
        //     'new_orders_revenue' => $new_orders_revenue,
        //     'views' => $views,
        // ]]);
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
            'account_type' => 'free_trial',
        ]);

        Product::factory()
            ->count($total_products)
            ->has(
                Order::factory()
                    ->count(2)
                    ->state([
                        'total_amount' => $total_amount,
                    ])
                    ->sequence(
                        ['created_at' => $within_range_date],
                        ['created_at' => $out_of_range_date],
                    )
                    ->has(
                        Customer::factory()
                            ->state([
                                'merchant_id' => $user->id,
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
            'end_date' => $end_date->toDateString(),
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
            'views',
        ]]);

        // $response->assertJson(['data' => [
        //     'total_products' => $total_products / 2, // Half of the total products will be within range
        //     'total_sales' => $total_sales / 2,
        //     'total_customers' => $total_customers / 2,
        //     'total_revenues' => $total_revenues / 2,
        //     'new_orders' => $new_orders,
        //     'new_orders_revenue' => $new_orders_revenue,
        //     'views' => $views,
        // ]]);
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
            'end_date' => $endDate,
        ]));
    }

    public function test_records(): void
    {
        // create user
        $user = User::factory()->create();
        $this->actingAs($user);

        Product::factory()->count(3)->create(['user_id' => $user->id]);

        $request = [
            'status' => ProductStatusEnum::Published->value,
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ];

        // act
        $response = $this->actingAs($user, 'web')
            ->withoutExceptionHandling()
            ->get(route('product.records'), $request);

        // assert
        $response->assertStatus(200);
        $response->assertHeader('Content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=products_'.now()->format('d_F_Y').'.csv');
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
            'format' => 'csv',
        ];

        // Send a request to the 'product.record' route, expecting an UnAuthorizedException to be thrown
        $this->withoutExceptionHandling()->get(route('product.records'), $request);
    }

    public function test_super_admin_can_export_products_with_date_filters()
    {
        $this->actingAsSuperAdmin();

        // Create products for testing
        Product::factory()->create(['title' => 'Product 1', 'price' => 100, 'created_at' => now()->subDays(10)]);
        Product::factory()->create(['title' => 'Product 2', 'price' => 200, 'created_at' => now()->subDays(5)]);

        // Call the adminRecords endpoint with date filters
        $response = $this->withoutExceptionHandling()->get(route('product.records.admin', [
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        // Assert response is successful and file is streamed
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=products_'.Carbon::today()->isoFormat('DD_MMMM_YYYY').'.csv');
    }

    public function test_admin_can_export_products_with_date_filters()
    {
        $this->actingAsAdmin();

        // Create products for testing
        Product::factory()->create(['title' => 'Product 1', 'price' => 100, 'created_at' => now()->subDays(10)]);
        Product::factory()->create(['title' => 'Product 2', 'price' => 200, 'created_at' => now()->subDays(5)]);

        // Call the adminRecords endpoint with date filters
        $response = $this->withoutExceptionHandling()->get(route('product.records.admin', [
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        // Assert response is successful and file is streamed
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=products_'.Carbon::today()->isoFormat('DD_MMMM_YYYY').'.csv');
    }

    public function test_super_admin_can_export_all_products_without_filters()
    {
        $this->actingAsSuperAdmin();

        // Create products for testing
        Product::factory()->count(3)->create();

        // Call the adminRecords endpoint without filters
        $response = $this->withoutExceptionHandling()->get(route('product.records.admin'));

        // Assert response is successful and file is streamed
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=products_'.Carbon::today()->isoFormat('DD_MMMM_YYYY').'.csv');
    }

    public function test_admin_can_export_all_products_without_filters()
    {
        $this->actingAsAdmin();

        // Create products for testing
        Product::factory()->count(3)->create();

        // Call the adminRecords endpoint without filters
        $response = $this->withoutExceptionHandling()->get(route('product.records.admin'));

        // Assert response is successful and file is streamed
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=products_'.Carbon::today()->isoFormat('DD_MMMM_YYYY').'.csv');
    }

    public function test_non_super_admin_cannot_export_products()
    {
        $this->actingAsRegularUser();

        // Call the adminRecords endpoint
        $response = $this->get(route('product.records.admin'));

        // Assert forbidden response
        $response->assertForbidden();
    }

    public function test_togglepublish(): void
    {
        // Create user
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Draft->value,
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
            'status' => ProductStatusEnum::Published->value,
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
            'status' => ProductStatusEnum::Draft->value,
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

        $expected_json = ExternalProductResource::collection($products)->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);
    }

    public function test_top_products_no_order(): void
    {
        $response = $this->get(route('product.top-products'));

        $expected_json = ExternalProductResource::collection([])->response()->getData(true);

        $response->assertStatus(200)->assertJson($expected_json, true);
    }

    public function test_super_admin_can_retrieve_best_selling_products_with_date_filters()
    {
        $this->actingAsSuperAdmin();

        $this->seed(ProductSeeder::class);

        Product::factory()->count(5)->has(Order::factory()->count(5), 'orders')->create([
            'user_id' => User::factory()->create()->id,
            'created_at' => now()->subYear(5),
        ]);

        // Call the bestSelling endpoint with date filters
        $response = $this->get(route('product.top-product.admin', [
            'start_date' => now()->subYear(6)->format('Y-m-d'),
            'end_date' => now()->subYear(4)->format('Y-m-d'),
        ]));

        // Assert response is successful and contains the expected products
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    public function test_admin_can_retrieve_best_selling_products_with_date_filters()
    {
        $this->actingAsAdmin();

        $this->seed(ProductSeeder::class);

        Product::factory()->count(5)->has(Order::factory()->count(5), 'orders')->create([
            'user_id' => User::factory()->create()->id,
            'created_at' => now()->subYear(5),
        ]);

        // Call the bestSelling endpoint with date filters
        $response = $this->get(route('product.top-product.admin', [
            'start_date' => now()->subYear(6)->format('Y-m-d'),
            'end_date' => now()->subYear(4)->format('Y-m-d'),
        ]));

        // Assert response is successful and contains the expected products
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    public function test_super_admin_can_retrieve_best_selling_products_without_filters()
    {
        $this->actingAsSuperAdmin();

        // Create products for testing
        $this->seed(ProductSeeder::class);

        // Call the bestSelling endpoint without filters
        $response = $this->get(route('product.top-product.admin'));

        // Assert response is successful and contains the expected products
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    public function test_admin_can_retrieve_best_selling_products_without_filters()
    {
        $this->actingAsAdmin();

        // Create products for testing
        $this->seed(ProductSeeder::class);

        // Call the bestSelling endpoint without filters
        $response = $this->get(route('product.top-product.admin'));

        // Assert response is successful and contains the expected products
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    public function test_non_super_admin_cannot_retrieve_best_selling_products()
    {
        $this->actingAsRegularUser();

        // Call the bestSelling endpoint
        $response = $this->get(route('product.top-product.admin'));

        // Assert forbidden response
        $response->assertForbidden();
    }

    public function test_delete(): void
    {
        // create user
        $user = User::factory()->create();

        $this->actingAs($user);

        $product = Product::factory()->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Draft->value,
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
            'status' => ProductStatusEnum::Draft->value,
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
            'status' => ProductStatusEnum::Draft->value,
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
            'status' => ProductStatusEnum::Draft->value,
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
            'status' => ProductStatusEnum::Draft->value,
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
            'status' => ProductStatusEnum::Draft->value,
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
            'status' => ProductStatusEnum::Draft->value,
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
            'status' => ProductStatusEnum::Draft->value,
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
            'status' => ProductStatusEnum::Draft->value,
        ]);

        // Act: Attempt to force delete the product as the first user
        $response = $this->withoutExceptionHandling()->delete(route('product.delete.force', ['product' => $product->id]));

        // Assert that the response status is 403
        $response->assertStatus(403);

        // Assert that the product still exists in the database
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_purchased(): void
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create another user who will be the product publisher
        $publisher = User::factory()->create();

        // Create products and associate them with the publisher
        $products = Product::factory()->count(3)->create([
            'user_id' => $publisher->id,
            'status' => ProductStatusEnum::Published->value,
        ]);

        // Create orders for the authenticated user
        foreach ($products as $product) {
            Order::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        // Act
        $response = $this->withoutExceptionHandling()->get(route('product.purchased'));

        // Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
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
        $response = $this->withoutExceptionHandling()->get(route('product.purchased'));

        // Assert that the response status is 401 Unauthorized
        $response->assertStatus(401);
    }

    public function test_tags(): void
    {
        $expectedTags = array_map(function ($tags) {
            return $tags->value;
        }, ProductTagsEnum::cases());

        // Act
        $response = $this->get(route('product.tags'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => $expectedTags,
        ]);
    }

    public function test_search(): void
    {
        // Create some sample products
        Product::factory()->create([
            'title' => 'Osh Product 1',
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);
        Product::factory()->create([
            'title' => 'Osh Product 2',
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);
        Product::factory()->create([
            'title' => 'Osh Product 3',
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);
        Product::factory()->create([
            'title' => 'Another Product',
            'description' => 'This is another osh product',
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);
        Product::factory()->create([
            'title' => 'full_name',
            'description' => 'Searching with full name',
            'user_id' => User::factory()->create(['full_name' => 'osh name']),
            'status' => ProductStatusEnum::Published->value,
        ]);

        $response = $this->post(route('product.search'), [
            'text' => 'osh',
        ]);

        // $response->dd();

        $response->assertOk()
            ->assertJsonPath('data', fn (array $data) => count($data) === 5) // Check count of returned data
            ->assertJsonPath('data.*.status', [
                0 => ProductStatusEnum::Published->value,
                1 => ProductStatusEnum::Published->value,
                2 => ProductStatusEnum::Published->value,
                3 => ProductStatusEnum::Published->value,
                4 => ProductStatusEnum::Published->value,
            ]);
    }

    public function test_search_sets_correct_cookie()
    {
        $products = Product::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $searchText = $products[0]->title;

        // Mock the search method to return a Builder instance
        $builder = Product::query()->whereIn('id', $products->pluck('id'));

        // Mock product repository
        $mock = $this->partialMock(ProductRepository::class);

        // Mock the search method
        $mock->shouldReceive('search')
            ->with($searchText)
            ->andReturn($builder);

        $response = $this->withoutExceptionHandling()->post(route('product.search'), ['text' => $searchText]);

        $product_ids = $products->pluck('id')->toArray();

        $expectedCookie = json_encode($product_ids);

        $this->assertEquals($expectedCookie, $response->headers->getCookies()[0]->getValue());
        $this->assertEquals('search_term', $response->headers->getCookies()[0]->getName());
    }

    public function test_search_handles_empty_search_text()
    {
        $response = $this->withoutExceptionHandling()->post(route('product.search'), ['text' => '']);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [],
        ]);
    }

    public function test_basedonsearch_returns_products_for_authenticated_user()
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Mock product repository
        $mock = $this->partialMock(ProductRepository::class);

        $collection = new Collection($products);

        // Mock the getFileMetaData method
        $mock->shouldReceive('findSearches')
            ->with($user)
            ->andReturn($collection);

        $this->actingAs($user);

        $response = $this->withoutExceptionHandling()->get(route('product.search.get'));

        $expected_json = ExternalProductResource::collection($products)->response()->getData(true);

        $response->assertStatus(200);
        $response->assertJson($expected_json);
    }

    public function test_basedOnSearch_returns_empty_collection_for_unauthenticated_user()
    {
        $response = $this->get(route('product.search.get'));

        $response->assertStatus(200);
        $response->assertJson(
            ['data' => []]
        );
    }

    public function testSendCongratulations()
    {
        $this->actingAsSuperAdmin();

        Mail::fake();
        Storage::fake('spaces');

        $user = User::factory()->create();
        $product = Product::factory()->has(Order::factory()->count(5), 'orders')->create([
            'user_id' => $user->id,
            'status' => ProductStatusEnum::Published->value,
            // 'thumbnail' => 'products-cover-photos/3d_collection_showcase-20210110-0001.jpg'
        ]);

        // Adding debugging to ensure the route is called
        $response = $this->withoutExceptionHandling()->post(route('product.congratulations', ['product' => $product->id]));

        $response->assertOk();

        // Assert the email was sent
        Mail::assertSent(BestSellerCongratulations::class, function ($mail) use ($product) {
            return $mail->hasTo($product->user->email) && $mail->product->is($product);
        });
    }
}
