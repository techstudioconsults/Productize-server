<?php

namespace App\Http\Controllers;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Http\Requests\StoreProductResourceRequest;
use App\Http\Resources\ProductDataResource;
use App\Models\Product;
use App\Models\ProductResource;
use App\Repositories\ProductRepository;
use App\Repositories\ProductResourceRepository;
use Auth;
use Illuminate\Http\Resources\Json\JsonResource;
use Storage;
use Str;

class ProductResourceController extends Controller
{
    public function __construct(
        protected ProductResourceRepository $productResourceRepository,
        protected ProductRepository $productRepository
    ) {
    }

    public function store(StoreProductResourceRequest $request)
    {
        $user = Auth::user();

        $entity = $request->validated();
        dd("i got here");

        $product = $this->productRepository->findById($entity['product_id']);

        var_dump($product);

        if (!$product) {
            throw new NotFoundException('Product Not Found');
        }

        if ($product->user->id !== $user->id) {
            throw new ForbiddenException("No Permission to access this product resource");
        }

        $resource = $entity['resource'];

        $name = Str::uuid() . '.' . $resource->extension(); // geneate a uuid

        $path = Storage::putFileAs("$product->product_type/" . ProductResourceRepository::PRODUCT_DATA_PATH, $resource, $name);

        $entity = [
            'name' => str_replace(' ', '', $resource->getClientOriginalName()),
            'url' => config('filesystems.disks.spaces.cdn_endpoint') . '/' . $path,
            'size' => $resource->getSize(),
            'mime_type' => $resource->getMimeType(),
            'extension' => $resource->extension(),
            'product_id' => $product->id
        ];

        $resource = $this->productResourceRepository->create($entity);

        return new ProductDataResource($resource);
    }

    public function product(Product $product)
    {
        $resources = $this->productResourceRepository->find(['product_id' => $product->id]);

        return ProductDataResource::collection($resources);
    }

    public function delete(ProductResource $resouce)
    {
        $this->productResourceRepository->deleteOne($resouce);

        return new JsonResource([
            'message' => 'Resource Deleted',
        ]);
    }
}
