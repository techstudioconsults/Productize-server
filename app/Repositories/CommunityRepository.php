<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 09-05-2024
 */


namespace App\Repositories;

use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Models\Community;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @author @obajide028 Odesanya Babajide
 *
 * Repository for community resource
 */

class CommunityRepository extends Repository
{
    public function seed(): void
    {
        Community::factory()->create()->count(10);
    }

    /**
     * @author @obajide028 Odesanya Babajide 
     *
     * Create a new community member.
     *
     * @param array $entity The data for creating the community member.
     * @return Community The newly created community instance.
     */
    public function create(array $entity): Community
    {
        if (!isset($entity['email'])) {
            throw new BadRequestException('No email Provided');
        }
        return Community::create($entity);
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Query community based on the provided filter.
     *
     * @param array $filter The filter criteria to apply.
     * @return Builder The query builder for community.
     */
    public function query(array $filter): Builder
    {
        $query = Community::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Find community member based on the provided filter.
     *
     * @param array|null $filter The filter criteria to apply (optional).
     * @return Collection The collection of found community member.
     */
    public function find(?array $filter = null): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @obajide028
     *
     * Find a community member by its ID.
     *
     * @param string $id The ID of the community member to find.
     * @return Community|null The found community member instance, or null if not found.
     */
    public function findById(string $id): ?Community
    {
        return Community::find($id);
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Find a single community member based on the provided filter.
     *
     * @param array $filter The filter criteria to apply.
     * @return Community|null The found cart instance, or null if not found.
     */
    public function findOne(array $filter): ?Community
    {
        return $this->query($filter)->first();
    }

    /**
     * @author @obajide028 Odesanya Babajide
     * 
     * Update a community member entity with the provided updates.
     *
     * @param Model $entity The community member entity to update.
     * @param array $updates The updates to apply to the community member.
     * @return Community The updated community instance.
     * @throws ModelCastException If the provided entity is not a Community instance.
     */
    public function update(Model $entity, array $updates): Community
    {
        if (!$entity instanceof Community) {
            throw new ModelCastException("Community", get_class($entity));
        }

        // Assign the updates to the corresponding fields of the User instance
        // It ignores keys passed but not present in model columns
        $entity->fill($updates);

        // Save the updated Community member instance
        $entity->save();

        // Return the updated Community member model
        return $entity;
    }
}
