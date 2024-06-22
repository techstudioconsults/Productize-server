<?php

/**
 * @author Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 08-05-2024
 */

namespace App\Repositories;

use App\Helpers\Services\ValidationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * The Repository class provides a template for repository classes.
 *
 * This abstract class extends the ValidationService, providing common validation
 * functionalities to all repository classes that extend it. It serves as a base
 * class for repositories, enabling them to inherit and utilize validation methods
 * and other shared behaviors.
 *
 * Key Features:
 * - Inherits validation capabilities from ValidationService.
 * - Serves as a common ancestor for all repository classes.
 *
 * @see \App\Console\Commands\MakeRepository on how to auto generate repository classes with predefined template
 */
abstract class Repository extends ValidationService
{
    /**
     * Seeds the repository with initial data.
     * It is suitable for unit and feature testing.
     */
    abstract public function seed(): void;

    /**
     * Creates a new entity in the database.
     *
     * @param array array of data for The entity to create.
     * @return Model The created entity.
     */
    abstract public function create(array $entity): Model;

    /**
     * Queries entities from the database based on the provided filter.
     *
     * @param  array|null  $filter  An optional filter to apply when retrieving entities.
     * @return \Illuminate\Database\Eloquent\Builder The query builder for retrieving entities.
     */
    abstract public function query(array $filter): Builder;

    /**
     * Applies filters to an Eloquent relation.
     *
     * This method accepts an Eloquent relation and an array of filters. It applies the filters
     * to the relation, including date filters if they are present in the filter array.
     * If the filter array is empty, the original relation is returned.
     *
     * @param  Relation  $relation  The Eloquent relation to which the filters will be applied.
     * @param  array  $filter  An associative array of filters to apply to the relation.
     *                         Supported filters include:
     *                         - 'start_date' and 'end_date': Apply a date range filter on the 'created_at' column.
     *                         - Other key-value pairs will be used as where conditions on the relation.
     * @return Relation The filtered Eloquent relation.
     *
     * @throws UnprocessableException If the date range filter is invalid.
     */
    public function queryRelation(Relation $relation, array $filter): Relation
    {
        if (empty($filter)) {
            return $relation;
        }

        $this->applyDateFilters($relation, $filter);

        return $relation->where($filter);
    }

    /**
     * Retrieves entities from the database based on the provided filter.
     *
     * @param  array|null  $filter  An optional filter to apply when retrieving entities.
     * @return \Illuminate\Database\Eloquent\Collection The collection for retrieved entities.
     */
    abstract public function find(?array $filter): ?Collection;

    /**
     * Retrieves a model by its id from the database.
     *
     * @param  string  $id  The unique identifier of the entity.
     * @return Model|null The entity corresponding to the given identifier, or null if not found.
     */
    abstract public function findById(string $id): ?Model;

    /**
     * Retrieves a model by an array of filter from the database.
     *
     * @param  string  $id  The unique identifier of the entity.
     * @return Model|null The entity corresponding to the given identifier, or null if not found.
     */
    abstract public function findOne(array $filter): ?Model;

    /**
     * Update an entity in the database.
     *
     * @param  Model  $entity  The model to be updated
     * @param  array  $updates  The array of data containing the fields to be updated.
     * @return Model The updated model
     */
    abstract public function update(Model $entity, array $updates): Model;

    /**
     * Update multiple entities in the database based on a given filter.
     *
     * @param  array  $filter  The filter to select entities to be updated.
     * @param  array  $updates  An associative array of data containing the fields to be updated.
     * @return int The total count of updated entities.
     */
    public function updateMany(array $filter, array $updates): int
    {
        return $this->find($filter)->update($updates);
    }

    /**
     * Delete an entity from the database
     *
     * @param  Model  $entity  The entity to be deleted.
     * @return bool True if the deletion was successful, false otherwise
     */
    public function deleteOne(Model $entity): bool
    {
        try {
            $entity->delete();

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Removes all entities from the database.
     */
    public function deleteMany(array $filter): int
    {
        return $this->find($filter)->delete();
    }
}
