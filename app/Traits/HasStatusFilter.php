<?php

namespace App\Traits;

use App\Enums\ProductStatusEnum;
use App\Exceptions\BadRequestException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\Rules\Enum;

trait HasStatusFilter
{
    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Intended for models implementing soft delete contracts
     *
     * Apply a status (deleted, draft, published) filter to the query based on the provided status value.
     * It removes the status key and value from the array.
     *
     * @param  Builder|Relation  $query  The query builder or relation instance.
     * @param  array  &$filter  The filter array containing the status key.
     *
     * @throws BadRequestException If the status value fails validation.
     */
    public function applyStatusFilter(Builder|Relation $query, array &$filter)
    {
        if (isset($filter['status'])) {
            $status = $filter['status'];

            if ($status === 'deleted') {
                $query->onlyTrashed();
            } elseif ($status && $status !== null && $status !== 'deleted') {
                // Validate status
                $rules = [
                    'status' => ['required', new Enum(ProductStatusEnum::class)],
                ];

                if (! $this->isValidated(['status' => $status], $rules)) {
                    throw new BadRequestException($this->getValidator()->errors()->first());
                }

                $query->where('status', $status);
            }
        }
        unset($filter['status']);
    }
}
