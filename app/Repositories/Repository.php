<?php

/**
 * @author Tobi Olanitori
 * @version 1.0
 * @since 08-05-2024
 */

namespace App\Repositories;

use App\Helpers\Services\ValidationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * The Repository class provides a template for repository classes.
 */
abstract class Repository extends ValidationService
{
    /**
     * Seeds the repository with initial data.
     * It is suitable for unit and feature testing.
     *
     * @return void
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
     * Retrieves entities from the database based on the provided filter.
     *
     * @param array|null $filter An optional filter to apply when retrieving entities.
     * @return \Illuminate\Database\Eloquent\Builder The query builder for retrieving entities.
     */
    abstract public function find(?array $filter): Builder;

    /**
     * Retrieves all entities from the database by a Model and an array of filter.
     *
     * The client model must have a callable method that defines its relationship with the server base model.
     *
     * @param Model $parent Use a model relation to retrieve entities.
     * @param  array $filter Associative array of filter colums and their value
     * @return Relation
     */
    abstract public function findByRelation(Model $parent, ?array $filter): Relation;

    /**
     * Retrieves a model by its id from the database.
     *
     * @param string $id The unique identifier of the entity.
     * @return Model|null The entity corresponding to the given identifier, or null if not found.
     */
    abstract public function findById(string $id): Model;

    /**
     * Retrieves a model by an array of filter from the database.
     *
     * @param string $id The unique identifier of the entity.
     * @return Model|null The entity corresponding to the given identifier, or null if not found.
     */
    abstract public function findOne(array $filter): Model;

    /**
     * Update an entity in the database.
     *
     * @param  Model $entity The model to be updated
     * @param array $updates The array of data containing the fields to be updated.
     * @return Model The updated model
     */
    abstract public function update(Model $entity, array $updates): Model;

    /**
     * Update multiple entities in the database based on a given filter.
     *
     * @param array $filter The filter to select entities to be updated.
     * @param array $updates An associative array of data containing the fields to be updated.
     * @return int The total count of updated entities.
     */
    public function updateMany(array $filter, array $updates): int
    {
        return $this->find($filter)->update($updates);
    }

    /**
     * Delete an entity from the database
     *
     * @param  Model $entity The entity to be deleted.
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
     *
     * @return int
     */
    public function deleteMany(array $filter): int
    {
        return $this->find($filter)->delete();
    }
}
