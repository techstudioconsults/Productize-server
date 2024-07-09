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
use App\Notifications\ProductCreated;
use App\Repositories\AssetRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SkillSellingRepository;
use Auth;
use DB;
use Illuminate\Http\Resources\Json\JsonResource;
use Log;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 04-07-2024
 */
class SkillSellingController extends Controller
{
    public function __construct(
        protected SkillSellingRepository $skillSellingRepository,
        protected AssetRepository $assetRepository,
        protected ProductRepository $productRepository,
    ) {}

    /**
     * Store a newly created skill selling resource in storage.
     *
     *
     * @return SkillSellingResource
     *
     * @throws ServerErrorException
     */
    public function store(StoreSkillSellingRequest $request)
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
            $skill_selling = DB::transaction(function () use ($assets, $product, $entity) {

                // Upload the assets to D.O spaces
                $assets = $this->assetRepository->uploadAssets($assets, $product->product_type);

                // Then save assets metadata in the db
                foreach ($assets as $asset) {
                    $this->assetRepository->create(['product_id' => $product->id, ...$asset]);
                }

                // Create skill selling
                return $this->skillSellingRepository->create($entity);
            });

            $user->notify(new ProductCreated($product));

            return new SkillSellingResource($skill_selling);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), [
                'endpoint' => '/api/digital-products',
                'method' => 'POST',
            ]);

            throw new ServerErrorException($th->getMessage(), 500);
        }
    }

    /**
     * Retrieve the specified Skill selling product.
     *
     * @param  SkillSelling  $skillselling
     * @return SkillsellingResource
     */
    public function show(SkillSelling $skillSelling)
    {
        return new SkillSellingResource($skillSelling);
    }

    /**
     * Retrieve the specified skill selling resource by its product id.
     *
     * @return SkillSellingResource
     */
    public function product(Product $product)
    {
        $skill_selling = $this->skillSellingRepository->findOne(['product_id' => $product->id]);

        if (! $skill_selling) {
            throw new NotFoundException('Resource Not Found');
        }

        return new SkillSellingResource($skill_selling);
    }

    /**
     * Update the specified skill selling resource in storage.
     *
     *
     *
     * @return SkillSellingResource
     */
    public function update(UpdateSkillSellingRequest $request, SkillSelling $skillSelling)
    {
        $validated = $request->validated();

        $updated_skill_selling = $this->skillSellingRepository->update($skillSelling, $validated);

        return new SkillSellingResource($updated_skill_selling);
    }

    /**
     * Retrieve all skill selling categories.
     *
     * @return JsonResource
     */
    public function categories()
    {
        return new JsonResource(SkillSellingCategory::cases());
    }
}
