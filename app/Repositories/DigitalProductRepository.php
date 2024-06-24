<?php

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Models\DigitalProduct;
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
 * Repository for DigitalProduct resource
 */
class DigitalProductRepository extends Repository
{
    /**
     * @author
     *
     * Create a new digitalProduct with the provided entity.
     *
     * @param  array  $entity  The digitalProduct data.
     *
     * @return DigitalProduct The newly created digitalProduct.
     */
    public function create(array $entity): DigitalProduct
    {
        return DigitalProduct::create($entity);
    }

    /**
     * @author
     *
     * Query digitalProduct based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for digitalProducts.
     */
    public function query(array $filter): Builder
    {
        $query = DigitalProduct::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author
     *
     * Find digitalProducts based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found digitalProducts.
     */
    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author
     *
     * Find a digitalProduct by their ID.
     *
     * @param  string  $id  The ID of the digitalProduct to find.
     * @return DigitalProduct|null The found digitalProduct instance, or null if not found.
     */
    public function findById(string $id): ?DigitalProduct
    {
        return DigitalProduct::find($id);
    }

    /**
     * @author
     *
     * Find a single digitalProduct based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return DigitalProduct|null The found digitalProduct instance, or null if not found.
     */
    public function findOne(array $filter): ?DigitalProduct
    {
        return DigitalProduct::where($filter)->firstOr(function () {
            return null;
        });
    }

    /**
     * @author
     *
     * Update an entity in the database.
     *
     * @param  Model  $entity  The digitalProduct to be updated
     * @param  array  $updates  The array of data containing the fields to be updated.
     * @return DigitalProduct The updated digitalProduct
     */
    public function update(Model $entity, array $updates): DigitalProduct
    {
        // Ensure that the provided entity is an instance of DigitalProduct
        if (!$entity instanceof DigitalProduct) {
            throw new ModelCastException('DigitalProduct', get_class($entity));
        }

        $entity->update($updates);
        return $entity;
    }
}
