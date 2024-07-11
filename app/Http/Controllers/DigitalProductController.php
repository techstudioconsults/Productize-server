<?php

namespace App\Http\Controllers;

use App\Enums\DigitalProductCategory;
use App\Exceptions\NotFoundException;
use App\Exceptions\ServerErrorException;
use App\Http\Requests\StoreDigitalProductRequest;
use App\Http\Resources\DigitalProductResource;
use App\Models\DigitalProduct;
use App\Models\Product;
use App\Notifications\ProductCreated;
use App\Repositories\AssetRepository;
use App\Repositories\DigitalProductRepository;
use App\Repositories\ProductRepository;
use Auth;
use DB;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class DigitalProductController extends Controller
{
    public function __construct(
        protected AssetRepository $assetRepository,
        protected ProductRepository $productRepository,
        protected DigitalProductRepository $digitalProductRepository,
    ) {}

    /**
     * Store a newly created digital product resource in storage.
     *
     *
     * @return DigitalProductResource
     *
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function store(StoreDigitalProductRequest $request)
    {
        $user = Auth::user();

        $entity = $request->validated();

        // Get the product from the request - See request class
        $product = $request->input('product');

        // Extract the assets from the product request
        $assets = $entity['assets'];
        unset($entity['assets']);

        try {
            // Initialize a transaction so the product is not persisted when there is an upload fail.
            $digital_product = DB::transaction(function () use ($assets, $product, $entity) {

                // Upload the assets to D.O spaces
                $assets = $this->assetRepository->uploadAssets($assets, $product->product_type);

                // Then save assets metadata in the db
                foreach ($assets as $asset) {
                    $this->assetRepository->create(['product_id' => $product->id, ...$asset]);
                }

                return $this->digitalProductRepository->create($entity);
            });

            $user->notify(new ProductCreated($product));

            return new DigitalProductResource($digital_product);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), [
                'endpoint' => '/api/digital-products',
                'method' => 'POST',
            ]);

            throw new ServerErrorException($th->getMessage(), 500);
        }
    }

    /**
     * Retrieve the specified digital product.
     *
     *
     * @return DigitalProductResource
     */
    public function show(DigitalProduct $digitalProduct)
    {
        return new DigitalProductResource($digitalProduct);
    }

    /**
     * Retrieve the specified digital product resource by its product.
     *
     *
     * @return DigitalProductResource
     *
     * @throws NotFoundException
     */
    public function product(Product $product)
    {
        $digital_product = $this->digitalProductRepository->findOne(['product_id' => $product->id]);

        if (! $digital_product) {
            throw new NotFoundException('Resource Not Foud');
        }

        return new DigitalProductResource($digital_product);
    }

    /**
     * Get all digital product categories.
     *
     * @return JsonResource
     */
    public function categories()
    {
        return new JsonResource(DigitalProductCategory::cases());
    }
}
