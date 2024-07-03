<?php

namespace App\Http\Controllers;

use App\Enums\SkillSellingCategory;
use App\Exceptions\NotFoundException;
use App\Exceptions\ServerErrorException;
use App\Http\Requests\StoreSkillSellingRequest;
use App\Http\Requests\UpdateSkillSellingRequest;
use App\Http\Resources\SkillSellingResource;
use App\Models\Product;
use App\Models\SkillSelling;
use App\Repositories\ProductRepository;
use App\Repositories\ProductResourceRepository;
use App\Repositories\SkillSellingRepository;
use DB;
use Illuminate\Http\Resources\Json\JsonResource;
use Log;

class SkillSellingController extends Controller
{
    public function __construct(
        protected SkillSellingRepository $skillSellingRepository,
        protected ProductResourceRepository $productResourceRepository,
        protected ProductRepository $productRepository,
    ) {}

    public function store(StoreSkillSellingRequest $request)
    {
        $entity = $request->validated();

        $product = $this->productRepository->findById($entity['product_id']);

        if (! $product) {
            throw new NotFoundException('Product Not Found');
        }

        $resources = $entity['resources'];
        unset($entity['resources']);

        try {
            $skill_selling = DB::transaction(function () use ($resources, $product, $entity) {

                $resources = $this->productResourceRepository->uploadResources($resources, $product->product_type);

                foreach ($resources as $resource) {
                    $this->productResourceRepository->create(['product_id' => $product->id, ...$resource]);
                }

                return $this->skillSellingRepository->create($entity);
            });

            return new SkillSellingResource($skill_selling);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), [
                'endpoint' => '/api/digital-products',
                'method' => 'POST',
            ]);

            throw new ServerErrorException($th->getMessage(), 500);
        }
    }

    public function show(Product $product)
    {
        $skill_selling = $this->skillSellingRepository->findOne(['product_id' => $product->id]);

        if (! $skill_selling) {
            throw new NotFoundException('Resource Not Found');
        }

        return new SkillSellingResource($skill_selling);
    }

    public function update(UpdateSkillSellingRequest $request, SkillSelling $skillSelling)
    {
        $validated = $request->validated();

        $updated_skill_selling = $this->skillSellingRepository->update($skillSelling, $validated);

        return new SkillSellingResource($updated_skill_selling);
    }

    public function categories()
    {
        return new JsonResource(SkillSellingCategory::cases());
    }
}
