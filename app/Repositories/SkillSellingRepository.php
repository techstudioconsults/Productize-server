<?php

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Models\SkillSelling;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @author
 *
 * @version 1.0
 *
 * @since
 *
 * Repository for SkillSelling resource
 */
class SkillSellingRepository extends Repository
{
    /**
     * @author
     *
     * Create a new skill Selling with the provided entity.
     *
     * @param  array  $entity  The skill Selling data.
     * @return SkillSelling The newly created skillSelling.
     */
    public function create(array $entity): SkillSelling
    {
        return SkillSelling::create($entity);
    }

    /**
     * @author
     *
     * Query skillSelling based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for skillSellings.
     */
    public function query(array $filter): Builder
    {
        $query = SkillSelling::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author
     *
     * Find skillSellings based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found skillSellings.
     */
    public function find(?array $filter = []): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author
     *
     * Find a skillSelling by their ID.
     *
     * @param  string  $id  The ID of the skillSelling to find.
     * @return SkillSelling|null The found skillSelling instance, or null if not found.
     */
    public function findById(string $id): ?SkillSelling
    {
        return SkillSelling::find($id);
    }

    /**
     * @author
     *
     * Find a single skillSelling based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return SkillSelling|null The found skillSelling instance, or null if not found.
     */
    public function findOne(array $filter): ?SkillSelling
    {
        return SkillSelling::where($filter)->firstOr(function () {
            return null;
        });
    }

    /**
     * @author
     *
     * Update an entity in the database.
     *
     * @param  Model  $entity  The skillSelling to be updated
     * @param  array  $updates  The array of data containing the fields to be updated.
     * @return SkillSelling The updated skillSelling
     */
    public function update(Model $entity, array $updates): SkillSelling
    {
        // Ensure that the provided entity is an instance of SkillSelling
        if (!$entity instanceof SkillSelling) {
            throw new ModelCastException('SkillSelling', get_class($entity));
        }

        $entity->update($updates);

        return $entity;
    }
}
