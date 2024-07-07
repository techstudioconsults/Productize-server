<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\Product;
use App\Repositories\AssetRepository;
use App\Repositories\ProductRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Storage;
use Str;

class AssetController extends Controller
{
    public function __construct(
        protected AssetRepository $assetRepository,
        protected ProductRepository $productRepository
    ) {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAssetRequest $request)
    {
        $entity = $request->validated();

        $product = $this->productRepository->findById($entity['product_id']);

        $asset = $entity['asset'];

        $name = Str::uuid() . '.' . $asset->extension(); // generate a uuid - save as file name in cloud

        $path = Storage::putFileAs("$product->product_type/" . AssetRepository::PRODUCT_DATA_PATH, $asset, $name);

        $entity = [
            'name' => str_replace(' ', '', $asset->getClientOriginalName()),
            'url' => config('filesystems.disks.spaces.cdn_endpoint') . '/' . $path,
            'size' => $asset->getSize(),
            'mime_type' => $asset->getMimeType(),
            'extension' => $asset->extension(),
            'product_id' => $product->id
        ];

        $asset = $this->assetRepository->create($entity);

        return new AssetResource($asset);
    }

    public function product(Product $product)
    {
        $assets = $this->assetRepository->find(['product_id' => $product->id]);

        return AssetResource::collection($assets);
    }

    public function delete(Asset $asset)
    {
        $this->assetRepository->deleteOne($asset);

        return new JsonResource([
            'message' => 'Asset Deleted',
        ]);
    }
}
