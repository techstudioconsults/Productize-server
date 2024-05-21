<?php

/**
 * @author Tobi Olanitori
 * @version 1.0
 * @since 08-05-2024
 */

namespace App\Repositories;

use App\Exceptions\UnprocessableException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Validator as Validation;
use Illuminate\Validation\Validator;

/**
 * The Repository class provides a template for repository classes.
 */
abstract class Repository
{
    /**
     * @var Validator|null $validator The validator instance used for validation.
     */
    private ?Validator $validator = null;

    /**
     * Get the current validator instance.
     *
     * This method returns the validator instance that is used for validating data.
     *
     * @return Validator|null The current validator instance or null if not set.
     */
    public function getValidator(): ?Validator
    {
        return $this->validator;
    }

    /**
     * Set the validator instance.
     *
     * This method sets the validator instance that will be used for validating data.
     *
     * @param Validator $validator The validator instance to set.
     * @return void
     */
    public function setValidator(Validator $validator): void
    {
        $this->validator = $validator;
    }

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

    /**
     * Validates the given data against the provided rules.
     *
     * This method uses Laravel's validation mechanism to check the given data
     * against the specified validation rules. If validation fails, it sets the
     * validator instance for later retrieval and returns false. If validation
     * passes, it returns true.
     *
     * @param array $data The data to be validated.
     * @param array $rules The validation rules to apply.
     * @return bool Returns true if validation passes, false otherwise.
     */
    protected function isValidated(array $data, array $rules): bool
    {
        // Validate the credentials
        $validator = validator($data, $rules);

        // Check if validation fails
        if ($validator->fails()) {
            $this->setValidator($validator);

            return false;
        };

        return true;
    }

    /**
     * Applies date filters to the given query.
     *
     * This method checks for 'start_date' and 'end_date' in the filter array.
     * If both are present, it validates the date range and applies a `whereBetween`
     * clause on the 'created_at' column of the query. The method also removes
     * 'start_date' and 'end_date' from the filter array after applying the filter.
     * If the date range is invalid, it throws an UnprocessableException.
     *
     * @param Builder|Relation $query The query to which the date filters will be applied.
     * @param array $filter The filter array containing 'start_date' and 'end_date'.
     * @throws UnprocessableException If the date range is invalid.
     */
    protected function applyDateFilters(Builder | Relation $query, array &$filter): void
    {
        // Check for start_date and end_date in the array
        if (isset($filter['start_date']) && isset($filter['end_date'])) {
            $start_date = $filter['start_date'];
            $end_date = $filter['end_date'];

            // Remove them from the array
            unset($filter['start_date'], $filter['end_date']);

            $isInvalid = $this->isInValidDateRange($start_date, $end_date);

            if ($isInvalid) {
                throw new UnprocessableException($this->getValidator()->errors()->first());
            }

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }
    }
}
