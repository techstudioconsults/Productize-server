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
        // Fake s3 storage
        Storage::fake('s3');

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
}
