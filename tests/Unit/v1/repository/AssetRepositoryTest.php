<?php

namespace Tests\Feature;

use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\Product;
use App\Repositories\AssetRepository;
use Aws\Multipart\UploadState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\TestCase;

class AssetRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AssetRepository $assetRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->assetRepository = new AssetRepository();
    }

    public function test_create_asset()
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

        $asset = $this->assetRepository->create($data);

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals($data['name'], $asset->name);
        $this->assertEquals($data['url'], $asset->url);
    }

    public function test_query_asset()
    {
        Asset::factory()->count(3)->create();

        $query = $this->assetRepository->query([]);

        $this->assertEquals(3, $query->count());
    }

    public function test_find_asset()
    {
        Asset::factory()->count(3)->create();

        $asset = $this->assetRepository->find([]);

        $this->assertCount(3, $asset);
    }

    public function test_find_by_id()
    {
        $asset = Asset::factory()->create();

        $result = $this->assetRepository->findById($asset->id);

        $this->assertInstanceOf(Asset::class, $result);
        $this->assertEquals($asset->id, $result->id);
    }

    public function test_find_one()
    {
        $asset = Asset::factory()->create();

        $result = $this->assetRepository->findOne(['id' => $asset->id]);

        $this->assertInstanceOf(Asset::class, $result);
        $this->assertEquals($asset->id, $result->id);
    }

    public function test_update()
    {
        $asset = Asset::factory()->create();
        $updates = ['name' => 'updated.pdf'];

        $updated = $this->assetRepository->update($asset, $updates);

        $this->assertEquals('updated.pdf', $updated->name);
    }

    public function test_update_throws_exception_for_invalid_model()
    {
        $this->expectException(ModelCastException::class);
        $invalidModel = new Product();
        $this->assetRepository->update($invalidModel, []);
    }

    public function test_upload_asset()
    {
        Storage::fake('spaces');

        $file = UploadedFile::fake()->create('document.pdf', 100);
        $productType = 'digital';

        $result = $this->assetRepository->uploadAssets([$file], $productType);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('url', $result[0]);
        $this->assertArrayHasKey('size', $result[0]);
        $this->assertArrayHasKey('mime_type', $result[0]);
        $this->assertArrayHasKey('extension', $result[0]);
    }

    public function test_upload_asset_throws_exception_for_invalid_data()
    {
        $this->expectException(BadRequestException::class);

        $this->assetRepository->uploadAssets(['not a file'], 'digital');
    }

    public function test_get_file_meta_data()
    {
        Storage::fake('spaces');

        $file = UploadedFile::fake()->create('document.pdf', 100);
        $path =  Storage::disk('spaces')->putFile('test', $file);

        $metadata = $this->assetRepository->getFileMetaData($path);

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('size', $metadata);
        $this->assertArrayHasKey('mime_type', $metadata);
    }

    public function test_get_file_meta_data_returns_null_for_non_existence_file()
    {
        Storage::fake('spaces');

        $filePath = 'non_existence_file.pdf';

        $metadata = $this->assetRepository->getFileMetaData($filePath);

        $this->assertNull($metadata);
    }
}
