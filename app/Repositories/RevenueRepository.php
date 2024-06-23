<?php

namespace App\Repositories;

use App\Models\Revenue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 22-06-2024
 *
 * Repository for Revenue resource
 */
class RevenueRepository extends Repository
{
    /**
     * Productize's Sale commission
     */
    const SALE_COMMISSION = 0.05;

    public function seed(): void
    {
        // Implementation of seed method
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Create a new revenue with the provided entity.
     *
     * @param  array  $entity  The revenue data.
     *                         Required keys: 'user_id', 'activity', 'product'.
     *                         Optional keys: 'amount', 'commission'.
     * @return \App\Models\Revenue The newly created revenue.
     */
    public function create(array $entity): Revenue
    {
        return Revenue::create($entity);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Query revenues based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for revenues.
     */
    public function query(array $filter): Builder
    {
        $query = Revenue::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find revenues based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found revenues.
     */
    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a revenue by their ID.
     *
     * @param  string  $id  The ID of the revenue to find.
     * @return Revenue|null The found revenue instance, or null if not found.
     */
    public function findById(string $id): ?Revenue
    {
        return Revenue::find($id);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a single revenue based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Revenue|null The found revenue instance, or null if not found.
     */
    public function findOne(array $filter): ?Revenue
    {
        return Revenue::where($filter)->firstOr(function () {
            return null;
        });
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Update an entity in the database.
     *
     * @param  Model  $entity  The revenue to be updated
     * @param  array  $updates  The array of data containing the fields to be updated.
     * @return Revenue The updated revenue
     */
    public function update(Model $entity, array $updates): Revenue
    {
        $entity->update($updates);

        return $entity;
    }
}
