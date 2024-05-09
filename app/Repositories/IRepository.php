<?php

/**
 * @author Tobi Olanitori
 * @version 1.0
 * @since 05-08-2024
 */

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * The Repository interface provides a template for repository classes.
 */
interface IRepository
{
    /**
     * Seeds the repository with initial data.
     * It is suitable for unit testing
     *
     * @return void
     */
    public function seed(): void;

    /**
     * Creates a new entity in the database.
     *
     * @param array array of data for The entity to create.
     * @return Model The created entity.
     */
    public function create(array $entity);

    /**
     * Retrieves all entities from the database.
     *
     * @return array<Model> A list of all entities stored in the database.
     */
    public function find(): array;


    /**
     * Retrieves all entities from the database by a filter
     *
     * @param  string $filter
     * @return array<Model>
     */
    public function findMany(string $filter): array;

    /**
     * Retrieves a model by its id from the repository.
     *
     * @param string $id The unique identifier of the entity.
     * @return Model|null The entity corresponding to the given identifier, or null if not found.
     */
    public function findById(string $id);

    /**
     * Update an entity in the database.
     *
     * @param  Model $entity The model to be updated
     * @param array $updates The array of data containing the fields to be updated.
     * @return Model The updated model
     */
    public function update(Model $entity, array $updates): Model;

    /**
     * Update multiple entities in the database based on a given filter.
     *
     * @param string $filter The filter to select entities to be updated.
     * @param array $updates An associative array of data containing the fields to be updated.
     * @return int The total count of updated entities.
     */
    public function updateMany(string $filter, array $updates): int;

    /**
     * Delete an entity from the database
     *
     * @param  Model $entity The entity to be deleted.
     * @return bool True if the deletion was successful, false otherwise
     */
    public function deleteOne(Model $entity): bool;

    /**
     * Removes all entities from the database.
     *
     * @return void
     */
    public function deleteMany(string $filter): void;
}
