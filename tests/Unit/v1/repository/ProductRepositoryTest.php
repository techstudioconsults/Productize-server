<?php

namespace Tests\Unit\v1\repository;

use App\Exceptions\BadRequestException;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProductRepository $productRepository;

    public function setUp(): void
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
            'user_id' => $this->user->id
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
            'data' => [UploadedFile::fake()->create('data_update.pdf')],
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
            'data' => [UploadedFile::fake()->create('data_update.pdf')],
            'cover_photos' => [UploadedFile::fake()->image('cover1_update.jpg')],
        ];

        $this->productRepository->update($product, $data);
    }

    public function test_upload_product_data(): void
    {
        // Fake spaces storage
        Storage::fake('spaces');

        $uploads = [
            UploadedFile::fake()->create('data1.pdf'),
            UploadedFile::fake()->create('data2.pdf'),
            UploadedFile::fake()->image('data3.png'),
            UploadedFile::fake()->image('data4.csv')
        ];

        $expected_result = [
            config('filesystems.disks.spaces.cdn_endpoint') . '/' . ProductRepository::PRODUCT_DATA_PATH . '/data1.pdf',
            config('filesystems.disks.spaces.cdn_endpoint') . '/' . ProductRepository::PRODUCT_DATA_PATH . '/data2.pdf',
            config('filesystems.disks.spaces.cdn_endpoint') . '/' . ProductRepository::PRODUCT_DATA_PATH . '/data3.png',
            config('filesystems.disks.spaces.cdn_endpoint') . '/' . ProductRepository::PRODUCT_DATA_PATH . '/data4.csv'
        ];

        $result = $this->productRepository->uploadData($uploads);

        Storage::disk('spaces')->assertExists(ProductRepository::PRODUCT_DATA_PATH . '/data1.pdf');
        Storage::disk('spaces')->assertExists(ProductRepository::PRODUCT_DATA_PATH . '/data2.pdf');
        Storage::disk('spaces')->assertExists(ProductRepository::PRODUCT_DATA_PATH . '/data3.png');
        Storage::disk('spaces')->assertExists(ProductRepository::PRODUCT_DATA_PATH . '/data4.csv');

        $this->assertEquals($expected_result[0], $result[0]);
        $this->assertEquals($expected_result[1], $result[1]);
        $this->assertEquals($expected_result[2], $result[2]);
        $this->assertEquals($expected_result[3], $result[3]);
    }

    public function test_upload_product_data_invalid_data_throws_400()
    {
        $this->expectException(BadRequestException::class);

        $this->productRepository->uploadData(["not a file", "not a file 2"]);
    }

    public function test_upload_product_thumbnail(): void
    {
        // Fake spaces storage
        Storage::fake('spaces');

        $file_name = 'thumbnail.png';

        $thumbnail = UploadedFile::fake()->image($file_name);

        $expected_result = config('filesystems.disks.spaces.cdn_endpoint') . '/' . ProductRepository::THUMBNAIL_PATH . "/$file_name";

        $result = $this->productRepository->uploadThumbnail($thumbnail);

        Storage::disk('spaces')->assertExists(ProductRepository::THUMBNAIL_PATH . "/$file_name");

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
            UploadedFile::fake()->image('data4.png')
        ];

        $expected_result = [
            config('filesystems.disks.spaces.cdn_endpoint') . '/' . ProductRepository::COVER_PHOTOS_PATH . '/data1.png',
            config('filesystems.disks.spaces.cdn_endpoint') . '/' . ProductRepository::COVER_PHOTOS_PATH . '/data2.jpg',
            config('filesystems.disks.spaces.cdn_endpoint') . '/' . ProductRepository::COVER_PHOTOS_PATH . '/data3.png',
            config('filesystems.disks.spaces.cdn_endpoint') . '/' . ProductRepository::COVER_PHOTOS_PATH . '/data4.png'
        ];

        $result = $this->productRepository->uploadCoverPhoto($uploads);

        Storage::disk('spaces')->assertExists(ProductRepository::COVER_PHOTOS_PATH . '/data1.png');
        Storage::disk('spaces')->assertExists(ProductRepository::COVER_PHOTOS_PATH . '/data2.jpg');
        Storage::disk('spaces')->assertExists(ProductRepository::COVER_PHOTOS_PATH . '/data3.png');
        Storage::disk('spaces')->assertExists(ProductRepository::COVER_PHOTOS_PATH . '/data4.png');

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
            UploadedFile::fake()->image('data4.csv')
        ];

        $this->productRepository->uploadCoverPhoto($uploads);
    }
}
