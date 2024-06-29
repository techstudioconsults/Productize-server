<?php

namespace App\Http\Controllers;

use App\Enums\SkillSellingCategory;
use App\Exceptions\NotFoundException;
use App\Http\Requests\StoreSkillSellingRequest;
use App\Http\Requests\UpdateSkillSellingRequest;
use App\Http\Resources\SkillSellingResource;
use App\Models\Product;
use App\Models\SkillSelling;
use App\Repositories\SkillSellingRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class SkillSellingController extends Controller
{
    public function __construct(protected SkillSellingRepository $skillSellingRepository)
    {
    }

    public function store(StoreSkillSellingRequest $request)
    {
        $entity = $request->validated();

        $skill_selling = $this->skillSellingRepository->create($entity);

        return new SkillSellingResource($skill_selling);
    }

    public function show(Product $product)
    {
        $skill_selling = $this->skillSellingRepository->findOne(['product_id' => $product->id]);

        if (!$skill_selling) throw new NotFoundException("Resource Not Found");
        
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
