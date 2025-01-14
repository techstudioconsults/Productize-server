<?php

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Models\EmailMarketing;
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
 * Repository for EmailMarketing resource
 */
class EmailMarketingRepository extends Repository
{
    /**
     * @author
     *
     * Create a new emailMarketing with the provided entity.
     *
     * @param  array  $entity  The emailMarketing data.
     *
     * @return EmailMarketing The newly created emailMarketing.
     */
    public function create(array $entity): EmailMarketing
    {
        return EmailMarketing::updateOrCreate(
            [
                'user_id' => $entity['user_id'],
                'provider' => $entity['provider'],
            ],
            [
                'token' => $entity['token'],
            ]
        );
    }

    /**
     * @author
     *
     * Query emailMarketing based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for emailMarketings.
     */
    public function query(array $filter): Builder
    {
        $query = EmailMarketing::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author
     *
     * Find emailMarketings based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found emailMarketings.
     */
    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author
     *
     * Find a emailMarketing by their ID.
     *
     * @param  string  $id  The ID of the emailMarketing to find.
     * @return EmailMarketing|null The found emailMarketing instance, or null if not found.
     */
    public function findById(string $id): ?EmailMarketing
    {
        return EmailMarketing::find($id);
    }

    /**
     * @author
     *
     * Find a single emailMarketing based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return EmailMarketing|null The found emailMarketing instance, or null if not found.
     */
    public function findOne(array $filter): ?EmailMarketing
    {
        return EmailMarketing::where($filter)->firstOr(function () {
            return null;
        });
    }

    /**
     * @author
     *
     * Update an entity in the database.
     *
     * @param  Model  $entity  The emailMarketing to be updated
     * @param  array  $updates  The array of data containing the fields to be updated.
     * @return EmailMarketing The updated emailMarketing
     */
    public function update(Model $entity, array $updates): EmailMarketing
    {
        // Ensure that the provided entity is an instance of EmailMarketing
        if (!$entity instanceof EmailMarketing) {
            throw new ModelCastException('EmailMarketing', get_class($entity));
        }

        $entity->update($updates);
        return $entity;
    }
}
