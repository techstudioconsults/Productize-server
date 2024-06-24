<?php

namespace App\Http\Controllers;

use App\Enums\DigitalProductCategory;
use App\Exceptions\NotFoundException;
use App\Exceptions\ServerErrorException;
use App\Http\Requests\StoreDigitalProductRequest;
use App\Http\Resources\DigitalProductResource;
use App\Repositories\DigitalProductRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductResourceRepository;
use DB;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class DigitalProductController extends Controller
{
    public function __construct(
        protected DigitalProductRepository $digitalProductRepository,
        protected ProductResourceRepository $productResourceRepository,
        protected ProductRepository $productRepository,
    ) {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDigitalProductRequest $request)
    {
        $entity = $request->validated();

        $product = $this->productRepository->findById($entity['product_id']);

        if (!$product) throw new NotFoundException("Product Not Found");

        $resources = $entity['resources'];
        unset($entity['resources']);

        try {
            $digital_product = DB::transaction(function () use ($resources, $product, $entity) {

                $resources = $this->productResourceRepository->uploadResources($resources, $product->product_type);

                foreach ($resources as $resource) {
                    $this->productResourceRepository->create(['product_id' => $product->id, ...$resource]);
                }

                return $this->digitalProductRepository->create($entity);
            });

            return new DigitalProductResource($digital_product);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), [
                'endpoint' => '/api/digital-products',
                'method' => 'POST'
            ]);

            throw new ServerErrorException($th->getMessage(), 500);
        }
    }

    public function categories()
    {
        return new JsonResource(DigitalProductCategory::cases());
    }
}
