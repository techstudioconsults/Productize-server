<?php

namespace App\Http\Controllers;

use App\Enums\SkillSellingCategory;
use App\Http\Requests\StoreSkillSellingRequest;
use App\Http\Resources\SkillSellingResource;
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

    public function categories()
    {
        return new JsonResource(SkillSellingCategory::cases());
    }
}
