<?php

namespace Tests\Unit\v1\repository;

use App\Enums\ProductStatusEnum;
use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ServerErrorException;
use App\Exceptions\UnprocessableException;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSearch;
use App\Models\User;
use App\Repositories\ProductRepository;
use App\Repositories\RevenueRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProductRepository $productRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = app(ProductRepository::class);

        // Create user with a free trial account, else test fails - check UserFactory.php
        $this->user = User::factory()->create(['account_type' => 'free_trial']);
    }

    public function test_create(): void
    {
        // Fake spaces storage
        Storage::fake('spaces');

        $data = [
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
            'user_id' => $this->user->id,
        ];

        $product = $this->productRepository->create($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($this->user->id, $product->user->id);
        $this->assertEquals('title', $product->title);

        $this->assertDatabaseHas('products', [
            'slug' => $product->slug,
        ]);
    }

    public function test_create_bad_data_throws_400(): void
    {
        $this->expectException(BadRequestException::class);

        $data = [
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

        // Attempt to create a product without a user_id
        $this->productRepository->create($data);
    }

    public function test_query_with_empty_filter_returns_all_products(): void
    {
        $products = Product::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $result = $this->productRepository->query([])->get();

        $this->assertCount(3, $result);
        $this->assertEquals($products->pluck('id')->sort()->values(), $result->pluck('id')->sort()->values());
    }

    public function test_query_with_date_filter_applies_date_range(): void
    {
        $products = Product::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $start_date = $products->first()->created_at->subDay()->toDateString();
        $end_date = $products->last()->created_at->addDay()->toDateString();

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $result = $this->productRepository->query($filter)->get();

        $this->assertCount(3, $result);
        $this->assertEquals($products->pluck('id')->sort()->values(), $result->pluck('id')->sort()->values());
    }

    public function test_query_with_invalid_date_filter_throws_exception(): void
    {
        $this->expectException(UnprocessableException::class);

        $filter = [
            'start_date' => 'invalid_date',
            'end_date' => '2024-12-31',
        ];

        $this->productRepository->query($filter);
    }

    public function test_query_with_status_filter_it_applies_deleted_status(): void
    {
        // Create products with different statuses
        Product::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $deletedProduct = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $deletedProduct->delete();

        $filter = [
            'status' => 'deleted',
        ];

        $query = $this->productRepository->query($filter);
        $products = $query->get();

        $this->assertCount(1, $products);
        $this->assertTrue($products->first()->trashed());
    }

    public function test_query_with_status_filter_it_applies_draft_status(): void
    {
        $expected_count = 6;

        // Create products with different statuses
        Product::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);

        // products are draft by default
        Product::factory()->count($expected_count)->create([
            'user_id' => $this->user->id,
        ]);

        $deletedProduct = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $deletedProduct->delete();

        $filter = [
            'status' => ProductStatusEnum::Draft->value,
        ];

        $query = $this->productRepository->query($filter);
        $products = $query->get();

        $this->assertCount($expected_count, $products);

        // Check that every product in the collection has the status 'draft'
        foreach ($products as $product) {
            $this->assertEquals(ProductStatusEnum::Draft->value, $product->status);
        }
    }

    public function test_query_with_status_filter_it_applies_published_status(): void
    {
        $expected_count = 6;

        // Create products with different statuses
        Product::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        // products are draft by default
        Product::factory()->count($expected_count)->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);

        $deletedProduct = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $deletedProduct->delete();

        $filter = [
            'status' => ProductStatusEnum::Published->value,
        ];

        $query = $this->productRepository->query($filter);
        $products = $query->get();

        $this->assertCount($expected_count, $products);
        foreach ($products as $product) {
            $this->assertEquals(ProductStatusEnum::Published->value, $product->status);
        }
    }

    public function test_query_with_status_filter_user_id(): void
    {
        $expected_count = 5;

        Product::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        Product::factory()->count($expected_count)->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $filter = [
            'user_id' => $this->user->id,
        ];

        $query = $this->productRepository->query($filter);
        $products = $query->get();

        $this->assertCount($expected_count, $products);

        foreach ($products as $product) {
            $this->assertEquals($this->user->id, $product->user->id);
        }
    }

    public function test_find()
    {
        $count = 10;

        Product::factory()->count($count)->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $result = $this->productRepository->find();

        // Assert
        $this->assertNotEmpty($result);
        $this->assertCount($count, $result);
        $this->assertInstanceOf(Product::class, $result->first());
    }

    public function test_findbyid(): void
    {
        $expected_result = Product::factory()->create(['user_id' => User::factory()->create()->id]);

        $result = $this->productRepository->findById($expected_result->id);

        $this->assertEquals($expected_result->id, $result->id);
    }

    public function test_findbyid_return_null_for_when_not_found(): void
    {
        $result = $this->productRepository->findById('id_does_not_exist');

        $this->assertNull($result);
    }

    public function test_findone(): void
    {
        $expected_result = Product::factory()->create(['user_id' => User::factory()->create()->id]);

        $result = $this->productRepository->findOne(['slug' => $expected_result->slug]);

        $this->assertEquals($expected_result->id, $result->id);
    }

    public function test_findone_return_null_when_not_found(): void
    {
        $result = $this->productRepository->findOne(['slug' => '12345']);

        $this->assertNull($result);
    }

    public function test_findone_throw_error_when_filter_key_not_on_table(): void
    {
        $this->expectException(ApiException::class);

        $this->productRepository->findOne(['slugs' => '12345']);
    }

    public function test_topproducts_returns_products_sorted_by_total_sales()
    {
        // Create products
        $products = Product::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);

        // Create orders for products
        Order::factory()->create(['product_id' => $products[0]->id, 'quantity' => 10]);
        Order::factory()->create(['product_id' => $products[1]->id, 'quantity' => 5]);
        Order::factory()->create(['product_id' => $products[2]->id, 'quantity' => 20]);

        // Call the method
        $topProducts = $this->productRepository->topProducts()->get();

        // Assertions
        $this->assertEquals(3, $topProducts->count());
        $this->assertEquals($products[2]->id, $topProducts->first()->id); // Product with highest sales
        $this->assertEquals(20, $topProducts->first()->total_sales);
    }

    public function test_top_products_with_date_filter()
    {
        // Create products
        $products = Product::factory()
            ->count(3)
            ->state(new Sequence(
                ['created_at' => Carbon::now()->subDays(5)],
                ['created_at' => Carbon::now()->subDays(15)],
                ['created_at' => Carbon::now()->subDays(25)]
            ))
            ->create(['user_id' => $this->user->id]);

        // Create orders for products with different created_at dates
        Order::factory()->create(['product_id' => $products[0]->id, 'quantity' => 10]);
        Order::factory()->create(['product_id' => $products[1]->id, 'quantity' => 5]);
        Order::factory()->create(['product_id' => $products[2]->id, 'quantity' => 20]);

        // Setting start_date and end_date to filter products created in the range
        $start_date = Carbon::now()->subDays(16)->toDateString();
        $end_date = Carbon::now()->toDateString();

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        // Call the method
        $topProducts = $this->productRepository->topProducts($filter)->get();

        // Assertions
        $this->assertCount(2, $topProducts); // All 2 products should be within the date range
        $this->assertEquals($products[0]->id, $topProducts->first()->id); // Product with highest sales within date range
        $this->assertEquals(10, $topProducts->first()->total_sales);
    }

    public function test_topproducts_with_status_filter()
    {
        // Create products with different statuses
        $publishedProduct = Product::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);
        $draftProduct = Product::factory()->create(['user_id' => $this->user->id, 'status' => 'draft']);

        // Create orders for products
        Order::factory()->create(['product_id' => $publishedProduct->id, 'quantity' => 10]);
        Order::factory()->create(['product_id' => $draftProduct->id, 'quantity' => 5]);

        // Apply status filter
        $filter = ['status' => 'published'];

        // Call the method
        $topProducts = $this->productRepository->topProducts($filter)->get();

        // Assertions
        $this->assertCount(1, $topProducts); // Only 1 product with published status
        $this->assertEquals($publishedProduct->id, $topProducts->first()->id); // Published product
        $this->assertEquals(10, $topProducts->first()->total_sales);
    }

    public function test_top_products_returns_empty_when_no_orders()
    {
        // Create products without orders
        Product::factory()->count(3)->create(['user_id' => $this->user->id]);

        // Call the method
        $topProducts = $this->productRepository->topProducts()->get();

        // Assertions
        $this->assertCount(0, $topProducts); // No products with orders
    }

    public function test_update(): void
    {
        // Fake spaces storage
        Storage::fake('spaces');

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $data = [
            'title' => 'title updated',
            'price' => 3,
            'thumbnail' => UploadedFile::fake()->image('avatar_update.jpg'),
            'description' => 'description',
            'cover_photos' => [UploadedFile::fake()->image('cover1_update.jpg')],
        ];

        $product = $this->productRepository->update($product, $data);

        $this->assertInstanceOf(Product::class, $product);

        $this->assertEquals($this->user->id, $product->user->id);
        $this->assertEquals('title updated', $product->title);
        $this->assertEquals(3, $product->price);
    }

    public function test_update_invalid_data_throws_400()
    {
        $this->expectException(BadRequestException::class);

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $data = [
            'title' => 1, // bad data type
            'price' => 3,
            'thumbnail' => UploadedFile::fake()->image('avatar_update.jpg'),
            'description' => 'description',
            'cover_photos' => [UploadedFile::fake()->image('cover1_update.jpg')],
        ];

        $this->productRepository->update($product, $data);
    }

    public function test_search_with_unpublished_products(): void
    {
        // Create some sample products
        Product::factory()->create(['title' => 'Sample Product 1', 'user_id' => $this->user->id]);
        Product::factory()->create(['title' => 'Sample Product 2', 'user_id' => $this->user->id]);
        Product::factory()->create([
            'title' => 'Sample Product 3',
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);
        Product::factory()->create([
            'title' => 'Another Product',
            'description' => 'This is another sample product',
            'user_id' => $this->user->id,
        ]);

        // Perform the search
        $results = $this->productRepository->search('Sample')->get();

        // Assertions
        $this->assertCount(1, $results); // Expecting 1 products with 'Sample' in title or description
        $this->assertEquals('Sample Product 3', $results[0]->title);
    }

    public function test_search_with_all_published_products(): void
    {
        // Create some sample products
        Product::factory()->create([
            'title' => 'Sample Product 1',
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);
        Product::factory()->create([
            'title' => 'Sample Product 2',
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);
        Product::factory()->create([
            'title' => 'Sample Product 3',
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);
        Product::factory()->create([
            'title' => 'Another Product',
            'description' => 'This is another sample product',
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);
        Product::factory()->create([
            'title' => 'full_name',
            'description' => 'Searching with full name',
            'user_id' => User::factory()->create(['full_name' => 'sample name']),
            'status' => ProductStatusEnum::Published->value,
        ]);

        // Perform the search
        $results = $this->productRepository->search('sample')->get();

        // Assertions
        $this->assertCount(5, $results); // Expecting 3 products with 'Sample' in title or description
        $this->assertEquals('Sample Product 1', $results[0]->title);
        $this->assertEquals('Sample Product 2', $results[1]->title);
        $this->assertEquals('Sample Product 3', $results[2]->title);
        $this->assertEquals('Another Product', $results[3]->title);
        $this->assertEquals('full_name', $results[4]->title);
    }

    public function test_tracksearch(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);

        $this->productRepository->trackSearch($product, $user);

        $this->assertDatabaseHas('product_searches', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseCount('product_searches', 1);
    }

    public function test_tracksearch_duplicate_user_product_pair_should_not_exist()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);

        // Track the search the first time
        $this->productRepository->trackSearch($product, $user);

        // Track the search the second time
        $this->productRepository->trackSearch($product, $user);

        $this->assertDatabaseHas('product_searches', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        // The second one should not be added - A pair product_id and user_id cannot be duplicate
        $this->assertDatabaseCount('product_searches', 1);
    }

    public function test_tracksearch_a_user_and_product_can_have_multiple_entries_if_pair_not_duplicate(): void
    {
        $user_one = User::factory()->create();
        $product_one = Product::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);

        $user_two = User::factory()->create();
        $product_two = Product::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);

        // Track the search the first time
        $this->productRepository->trackSearch($product_one, $user_one);

        // Track the search the second time
        $this->productRepository->trackSearch($product_one, $user_two);

        // Track the search the third time
        $this->productRepository->trackSearch($product_two, $user_one);

        // Track the search the fourth time
        $this->productRepository->trackSearch($product_two, $user_two);

        $this->assertDatabaseCount('product_searches', 4);
    }

    public function test_findsearches(): void
    {
        $user = User::factory()->create();

        $expected = Product::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);

        ProductSearch::factory()
            ->count(2)
            ->state(new Sequence(
                ['product_id' => $expected[0]->id],
                ['product_id' => $expected[1]->id],
            ))
            ->create([
                'user_id' => $user->id,
            ]);

        ProductSearch::factory()
            ->count(2)
            ->state(new Sequence(
                ['product_id' => Product::factory()->create(['user_id' => User::factory()->create()->id])],
                ['product_id' => Product::factory()->create(['user_id' => User::factory()->create()->id])],
            ))
            ->create([
                'user_id' => User::factory()->create()->id,
            ]);

        $results = $this->productRepository->findSearches($user);

        $this->assertCount(2, $results);
        $this->assertDatabaseCount('product_searches', 4);
    }

    public function test_findsearches_draft_products_should_not_be_returned(): void
    {
        $user = User::factory()->create();

        $expected = Product::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => ProductStatusEnum::Published->value,
        ]);

        ProductSearch::factory()
            ->count(2)
            ->state(new Sequence(
                ['product_id' => $expected[0]->id],
                ['product_id' => $expected[1]->id],
            ))
            ->create([
                'user_id' => $user->id,
            ]);

        // change the status of the first product to draft
        $expected[0]->status = ProductStatusEnum::Draft->value;
        $expected[0]->save();
        $expected[0]->refresh();

        $results = $this->productRepository->findSearches($user);

        // Only the published product is returned
        $this->assertCount(1, $results);
        $this->assertEquals($expected[1]->id, $results[0]->id);
    }

    public function test_upload_product_thumbnail(): void
    {
        // Fake spaces storage
        Storage::fake('spaces');

        $file_name = 'thumbnail.png';

        $thumbnail = UploadedFile::fake()->image($file_name);

        $expected_result = config('filesystems.disks.spaces.cdn_endpoint').'/'.ProductRepository::THUMBNAIL_PATH."/$file_name";

        $result = $this->productRepository->uploadThumbnail($thumbnail);

        Storage::disk('spaces')->assertExists(ProductRepository::THUMBNAIL_PATH."/$file_name");

        $this->assertEquals($expected_result, $result);
    }

    public function test_upload_product_thumbnail_invalid_image(): void
    {
        $this->expectException(BadRequestException::class);

        $this->productRepository->uploadThumbnail(UploadedFile::fake()->create('not_an_image.pdf'));
    }

    public function test_upload_cover_photos(): void
    {
        // Fake spaces storage
        Storage::fake('spaces');

        $uploads = [
            UploadedFile::fake()->create('data1.png'),
            UploadedFile::fake()->create('data2.jpg'),
            UploadedFile::fake()->image('data3.png'),
            UploadedFile::fake()->image('data4.png'),
        ];

        $expected_result = [
            config('filesystems.disks.spaces.cdn_endpoint').'/'.ProductRepository::COVER_PHOTOS_PATH.'/data1.png',
            config('filesystems.disks.spaces.cdn_endpoint').'/'.ProductRepository::COVER_PHOTOS_PATH.'/data2.jpg',
            config('filesystems.disks.spaces.cdn_endpoint').'/'.ProductRepository::COVER_PHOTOS_PATH.'/data3.png',
            config('filesystems.disks.spaces.cdn_endpoint').'/'.ProductRepository::COVER_PHOTOS_PATH.'/data4.png',
        ];

        $result = $this->productRepository->uploadCoverPhoto($uploads);

        Storage::disk('spaces')->assertExists(ProductRepository::COVER_PHOTOS_PATH.'/data1.png');
        Storage::disk('spaces')->assertExists(ProductRepository::COVER_PHOTOS_PATH.'/data2.jpg');
        Storage::disk('spaces')->assertExists(ProductRepository::COVER_PHOTOS_PATH.'/data3.png');
        Storage::disk('spaces')->assertExists(ProductRepository::COVER_PHOTOS_PATH.'/data4.png');

        $this->assertEquals($expected_result[0], $result[0]);
        $this->assertEquals($expected_result[1], $result[1]);
        $this->assertEquals($expected_result[2], $result[2]);
        $this->assertEquals($expected_result[3], $result[3]);
    }

    public function test_upload_cover_photos_invalid_images(): void
    {
        $this->expectException(BadRequestException::class);

        $uploads = [
            UploadedFile::fake()->create('data1.pdf'),
            UploadedFile::fake()->create('data2.pdf'),
            UploadedFile::fake()->image('data3.png'),
            UploadedFile::fake()->image('data4.csv'),
        ];

        $this->productRepository->uploadCoverPhoto($uploads);
    }

    public function test_is_searchedproduct_with_valid_cookie()
    {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $cookie = json_encode([$product->id]);

        $result = $this->productRepository->isSearchedProduct($product, $cookie);

        $this->assertTrue($result);
    }

    public function test_is_searchedproduct_with_empty_cookie()
    {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $cookie = json_encode([]);

        $result = $this->productRepository->isSearchedProduct($product, $cookie);

        $this->assertFalse($result);
    }

    public function test_is_searchedproduct_with_invalid_cookie()
    {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $cookie = 'invalid_json';

        $result = $this->productRepository->isSearchedProduct($product, $cookie);

        $this->assertFalse($result);
    }

    public function test_is_searched_product_with_null_cookie()
    {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $cookie = null;

        $result = $this->productRepository->isSearchedProduct($product, $cookie);

        $this->assertFalse($result);
    }

    public function test_ispublished_with_published_product()
    {
        $product = Product::factory()->create([
            'status' => ProductStatusEnum::Published->value,
            'user_id' => $this->user->id,
        ]);

        $result = $this->productRepository->isPublished($product);

        $this->assertTrue($result);
    }

    public function test_ispublished_with_unpublished_product()
    {
        $product = Product::factory()->create([
            'status' => ProductStatusEnum::Draft->value,
            'user_id' => $this->user->id,
        ]);

        $result = $this->productRepository->isPublished($product);

        $this->assertFalse($result);
    }

    public function test_prepare_products_valid_data()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 1000, 'status' => 'published', 'discount_price' => 900]);

        $cart = [
            ['product_slug' => $product->slug, 'quantity' => 2],
        ];

        // Act
        $result = $this->productRepository->prepareProducts($cart);

        // Assert
        $discountedPrice = 900;
        $amount = $discountedPrice * 2;
        $share = $amount - ($amount * RevenueRepository::SALE_COMMISSION);

        // Assert
        $expected = [
            [
                'product_id' => $product->id,
                'amount' => $amount,
                'quantity' => 2,
                'share' => $share,
                'price' => $discountedPrice,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_prepare_products_valid_data_no_discount()
    {
        // Arrange
        $product = Product::factory()->create([
            'price' => 1000,
            'status' => 'published',
            'discount_price' => 0,  // Explicitly set discount to 0
        ]);

        $cart = [
            ['product_slug' => $product->slug, 'quantity' => 2],
        ];

        // Act
        $result = $this->productRepository->prepareProducts($cart);

        // Assert
        $amount = 1000 * 2;
        $share = $amount - ($amount * RevenueRepository::SALE_COMMISSION);

        $expected = [
            [
                'product_id' => $product->id,
                'amount' => $amount,
                'quantity' => 2,
                'share' => $share,
                'price' => 1000,  // Original price, no discount applied
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_prepare_products_invalid_data()
    {
        // Arrange
        $cart = [
            ['product_slug' => '', 'quantity' => 2],
        ];

        // Expect
        $this->expectException(ServerErrorException::class);

        // Act
        $this->productRepository->prepareProducts($cart);
    }

    public function test_prepare_products_product_not_found()
    {
        // Arrange
        $cart = [
            ['product_slug' => 'non-existent-slug', 'quantity' => 2],
        ];

        // Expect
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Product with slug non-existent-slug not found');

        // Act
        $this->productRepository->prepareProducts($cart);
    }

    public function test_prepare_products_product_not_published()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 1000, 'status' => 'draft']);

        $cart = [
            ['product_slug' => $product->slug, 'quantity' => 2],
        ];

        // Expect
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage("Product with slug $product->slug not published");

        // Act
        $this->productRepository->prepareProducts($cart);
    }
}
