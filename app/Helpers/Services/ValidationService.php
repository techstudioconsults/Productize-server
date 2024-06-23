<?php

/**
 * @author Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 21-05-2024
 */

namespace App\Helpers\Services;

use App\Exceptions\UnprocessableException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Validator as Validation;
use Illuminate\Validation\Validator;

/**
 * The ValidationService class provides methods for validating data and applying date filters to queries.
 *
 * This service is used to ensure data integrity by validating input data against specified rules
 * and to handle date range filtering on database queries.
 */
class ValidationService
{
    /**
     * @var Validator|null The validator instance used for validation.
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
     * @param  Validator  $validator  The validator instance to set.
     */
    public function setValidator(Validator $validator): void
    {
        $this->validator = $validator;
    }

    /**
     * It validates a date range is invalid.
     * Returns true if date range is invalid, returns false otherwise.
     */
    protected function isInValidDateRange(string $start_date, string $end_date): bool
    {
        /**
         * Validator is imported as Validator. Check top of the file.
         */
        $validator = Validation::make([
            'start_date' => $start_date,
            'end_date' => $end_date,
        ], [
            'start_date' => 'date',
            'end_date' => 'date',
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
     * @param  array  $data  The data to be validated.
     * @param  array  $rules  The validation rules to apply.
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
        }

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
     * @param  Builder|Relation  $query  The query to which the date filters will be applied.
     * @param  array  $filter  The filter array containing 'start_date' and 'end_date'.
     *
     * @throws UnprocessableException If the date range is invalid.
     */
    protected function applyDateFilters(Builder|Relation $query, array &$filter): void
    {
        // Check for start_date and end_date in the array
        if (array_key_exists('start_date', $filter) && array_key_exists('end_date', $filter)) {
            $start_date = $filter['start_date'] ?? ''; // Possibly null
            $end_date = $filter['end_date'] ?? ''; // Possibly null

            // Remove them from the array
            unset($filter['start_date'], $filter['end_date']);

            // Exit
            if (! $start_date || ! $end_date) {
                return;
            }

            $isInvalid = $this->isInValidDateRange($start_date, $end_date);

            if ($isInvalid) {
                throw new UnprocessableException($this->getValidator()->errors()->first());
            }

            $query->whereBetween('created_at', [
                Carbon::parse($start_date)->startOfDay(),
                Carbon::parse($end_date)->endOfDay(),
            ]);
        }
    }
}
