<?php

namespace App\Http\Controllers;

use App\Exceptions\ServerErrorException;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\Product;
use App\Repositories\AssetRepository;
use App\Repositories\ProductRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Log;
use Storage;
use Str;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 26-06-2024
 *
 * Controller for managing assets.
 */
class AssetController extends Controller
{
    public function __construct(
        protected AssetRepository $assetRepository,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Store a newly created asset in storage.
     *
     * @return AssetResource
     */
    public function store(StoreAssetRequest $request)
    {
        $entity = $request->validated();

        // Get the product merged from the StoreAssetRequest
        $product = $request->input('product');

        // Get the product's asset
        $asset = $entity['asset'];

        // Name the asset using uuid string
        $name = Str::uuid().'.'.$asset->extension(); // generate a uuid - save as file name in cloud

        // Upload the asset
        $path = Storage::putFileAs("$product->product_type/".AssetRepository::PRODUCT_DATA_PATH, $asset, $name);

        // Arrange the data for storage
        $entity = [
            'name' => str_replace(' ', '', $asset->getClientOriginalName()),
            'url' => config('filesystems.disks.spaces.cdn_endpoint').'/'.$path,
            'size' => $asset->getSize(),
            'mime_type' => $asset->getMimeType(),
            'extension' => $asset->extension(),
            'product_id' => $product->id,
        ];

        $asset = $this->assetRepository->create($entity);

        return new AssetResource($asset);
    }

    /**
     * Retrieve a list of the assets for a given product.
     *
     * @param  Product  $product  Product associated with the Asset
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<Asset>
     */
    public function product(Product $product)
    {
        $assets = $this->assetRepository->find(['product_id' => $product->id]);

        return AssetResource::collection($assets);
    }

    /**
     * Remove the specified asset from storage.
     *
     * @return JsonResource
     */
    public function delete(Asset $asset)
    {
        try {
            $isDeleted = $asset->forceDelete();

            if (! $isDeleted) {
                throw new ServerErrorException('Asset Not Deleted');
            }

            return new JsonResource([
                'message' => 'Asset Deleted',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting asset: '.$e->getMessage());
            throw new ServerErrorException('Asset Not Deleted');
        }
    }
}
