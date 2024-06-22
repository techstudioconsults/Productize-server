<?php

namespace App\Repositories;

use App\Models\Revenue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class RevenueRepository extends Repository
{
    public function seed(): void
    {
        // Implementation of seed method
    }

    public function create(array $entity): Revenue
    {
        return Revenue::create($entity);
    }

    public function query(array $filter): Builder
    {
        return Revenue::query()->where($filter);
    }

    public function find(?array $filter): ?Collection
    {
        return Revenue::where($filter)->get();
    }

    public function findById(string $id): ?Revenue
    {
        return Revenue::find($id);
    }

    public function findOne(array $filter): ?Revenue
    {
        return Revenue::where($filter)->first();
    }

    public function update(Model $entity, array $updates): Revenue
    {
        $entity->update($updates);
        return $entity;
    }
}