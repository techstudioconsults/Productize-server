<?php

/**
 * @author Tobi Olanitori
 * @version 1.0
 * @since 05-08-2024
 */

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator as Validation;
use Illuminate\Validation\Validator;

/**
 * The Repository class provides a template for repository classes.
 */
abstract class Repository
{
    private ?Validator $validator = null;

    public function getValidator(): ?Validator
    {
        return $this->validator;
    }

    public function setValidator(Validator $validator): void
    {
        $this->validator = $validator;
    }

    /**
     * Seeds the repository with initial data.
     * It is suitable for unit testing
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
    abstract public function create(array $entity);

    /**
     * Retrieves all entities from the database.
     *
     * @return array<Model> A list of all entities stored in the database.
     */
    abstract public function find(): array;

    /**
     * Retrieves all entities from the database by a filter
     *
     * @param  array $filter Associative array of filter colums and their value
     * @return array<Model>
     */
    abstract public function findMany(array $filter): array;

    /**
     * Retrieves a model by its id from the repository.
     *
     * @param string $id The unique identifier of the entity.
     * @return Model|null The entity corresponding to the given identifier, or null if not found.
     */
    abstract public function findById(string $id);

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
     * @param string $filter The filter to select entities to be updated.
     * @param array $updates An associative array of data containing the fields to be updated.
     * @return int The total count of updated entities.
     */
    abstract public function updateMany(string $filter, array $updates): int;

    /**
     * Delete an entity from the database
     *
     * @param  Model $entity The entity to be deleted.
     * @return bool True if the deletion was successful, false otherwise
     */
    abstract public function deleteOne(Model $entity): bool;

    /**
     * Removes all entities from the database.
     *
     * @return void
     */
    abstract public function deleteMany(string $filter): void;

    /**
     * It validates a date range is invalid.
     * Returns true if date range is invalid, returns false otherwise.
     *
     * @param  string $start_date
     * @param  string $end_date
     * @return bool
     */
    protected function isInValidDateRange(string $start_date, string $end_date)
    {
        /**
         * Validator is imported as Validator. Check top of the file.
         */
        $validator = Validation::make([
            'start_date' => $start_date,
            'end_date' => $end_date
        ], [
            'start_date' => 'date',
            'end_date' => 'date'
        ]);

        if ($validator->fails()) {
            // Set current validator object.
            $this->setValidator($validator);

            return true;
        }

        return false;
    }
}
