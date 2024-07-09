<?php

namespace Tests\Feature;

use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Models\Product;
use App\Models\ProductResource;
use App\Repositories\ProductResourceRepository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Storage;
use Tests\TestCase;

class ProductResourceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProductResourceRepository $productResourceRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->productResourceRepository = new ProductResourceRepository();
    }

    public function test_create_product_resource()
    {
        Storage::fake('spaces');

        $data = [
            'name' => 'test.pdf',
            'url' => 'https://example.com/test.pdf',
            'size' => 1000,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'product_id' => Product::factory()->create()->id
        ];

        $resource = $this->productResourceRepository->create($data);


        $this->assertInstanceOf(ProductResource::class, $resource);
        $this->assertEquals($data['name'], $resource->name);
        $this->assertEquals($data['url'], $resource->url);
    }

    public function test_query_product_resources()
    {
        ProductResource::factory()->count(3)->create();

        $query = $this->productResourceRepository->query([]);

        $this->assertEquals(3, $query->count());
    }

    public function test_find_product_resources()
    {
        ProductResource::factory()->count(3)->create();

        $resources = $this->productResourceRepository->find([]);

        $this->assertCount(3, $resources);
    }

    public function test_find_by_id()
    {
        $resource = ProductResource::factory()->create();

        $result = $this->productResourceRepository->findById($resource->id);

        $this->assertInstanceOf(ProductResource::class, $result);
        $this->assertEquals($resource->id, $result->id);
    }

    public function test_find_one()
    {
        $resource = ProductResource::factory()->create();

        $result = $this->productResourceRepository->findOne(['id' => $resource->id]);

        $this->assertInstanceOf(ProductResource::class, $result);
        $this->assertEquals($resource->id, $result->id);
    }

    public function test_update()
    {
        $resource = ProductResource::factory()->create();
        $updates = ['name' => 'updated.pdf'];

        $updated = $this->productResourceRepository->update($resource, $updates);

        $this->assertEquals('updated.pdf', $updated->name);
    }

    public function test_update_throws_exception_for_invalid_model()
    {
        $this->expectException(ModelCastException::class);

        $invalidModel = new Product();
        $this->productResourceRepository->update($invalidModel, []);
    }

    public function test_upload_resources()
    {
        Storage::fake('spaces');

        $file = UploadedFile::fake()->create('document.pdf', 100);
        $productType ='digital';
     
        $result = $this->productResourceRepository->uploadResources([$file], $productType);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('url', $result[0]);
        $this->assertArrayHasKey('size', $result[0]);
        $this->assertArrayHasKey('mime_type', $result[0]);
        $this->assertArrayHasKey('extension', $result[0]);
    }

    public function test_upload_resources_throws_exception_for_invalid_data()
    {
        $this->expectException(BadRequestException::class);

        $this->productResourceRepository->uploadResources(['not a file'], 'digital');
    }

    public function test_get_file_meta_data()
    {
        Storage::fake('spaces');

        $file = UploadedFile::fake()->create('document.pdf', 100);
        $path = Storage::disk('spaces')->putFile('test', $file);

        $metadata = $this->productResourceRepository->getFileMetaData($path);

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('size', $metadata);
        $this->assertArrayHasKey('mime_type', $metadata);
    }

    public function test_get_file_meta_data_returns_null_for_non_existent_file()
    {
      
        Storage::fake('spaces');

        $filePath = 'non_existent_file.pdf';
        
       //$this->storageMock->shouldReceive('exists')->with($filePath)->andReturn(false);

        $metadata = $this->productResourceRepository->getFileMetaData($filePath);

        $this->assertNull($metadata);
    }
}
