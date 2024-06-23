<?php

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Models\Complaint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 23-06-2024
 *
 * Repository for Complaint resource
 */
class ComplaintRepository extends Repository
{
    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Create a new complaint.
     *
     * @param  array  $entity  The data for creating the complaint.
     * @return Complaint The newly created complaint instance.
     */
    public function create(array $entity): Complaint
    {
        return Complaint::create($entity);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Query complaints based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for complaints.
     */
    public function query(?array $filter = []): Builder
    {
        $query = Complaint::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find complaints based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found complaints.
     */
    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a complaint by its ID.
     *
     * @param  string  $id  The ID of the complaint to find.
     * @return Complaint|null The found complaint instance, or null if not found.
     */
    public function findById(string $id): ?Complaint
    {
        return Complaint::find($id);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a single complaint based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Complaint|null The found complaint instance, or null if not found.
     */
    public function findOne(array $filter): ?Complaint
    {
        return Complaint::where($filter)->firstOr(function () {
            return null;
        });
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Update a complaint entity with the provided updates.
     *
     * @param  Model  $entity  The complaint entity to update.
     * @param  array  $updates  The updates to apply to the complaint.
     * @return Complaint The updated complaint instance.
     *
     * @throws ModelCastException If the provided entity is not a Complaint instance.
     */
    public function update(Model $entity, array $updates): Complaint
    {
        if (!$entity instanceof Complaint) {
            throw new ModelCastException('Complaint', get_class($entity));
        }

        $entity->update($updates);
        return $entity;
    }
}
