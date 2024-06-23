<?php

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 30-05-2024
 */

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Models\Earning;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 30-05-2024
 *
 * Repository for Earning resource
 */
class EarningRepository extends Repository
{
    /**
     *  @author @Intuneteq Tobi Olanitori
     *
     * Create or update the total earnings for a specific user.
     *
     * @param  array  $entity  The array containing the entity data.
     *                         Example:
     *                         [
     *                         'user_id' => 1,
     *                         'amount' => 100.00
     *                         ]
     * @return Earning The updated or newly created Earning entity.
     */
    public function create(array $entity): Earning
    {
        $user_id = $entity['user_id'];
        $amount = $entity['amount'];

        $earning = Earning::firstOrCreate([
            'user_id' => $user_id,
        ]);

        $earning->total_earnings = $earning->total_earnings + $amount;

        $earning->save();

        return $earning;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Query earnings based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for earnings.
     */
    public function query(array $filter): Builder
    {
        $query = Earning::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find earnings based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found earnings.
     */
    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a earning by its ID.
     *
     * @param  string  $id  The ID of the earning to find.
     * @return Earning|null The found earning instance, or null if not found.
     */
    public function findById(string $id): ?Earning
    {
        return Earning::find($id);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a single earning based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Earning|null The found cart instance, or null if not found.
     */
    public function findOne(array $filter): ?Earning
    {
        return Earning::where($filter)->firstOr(function () {
            return null;
        });
    }

    /**
     * @author @Intuneteq
     *
     * Update the fields of an existing Earning entity.
     *
     * @param  Model  $entity  The Earning entity to update.
     * @param  array  $updates  The associative array of fields to update.
     *                          Example:
     *                          [
     *                          'total_earnings' => 500,
     *                          'withdrawn_earnings' => 100,
     *                          ]
     * @return Earning The updated Earning entity.
     *
     * @throws ModelCastException If $entity is not an instance of Earning.
     */
    public function update(Model $entity, array $updates): Earning
    {
        if (! $entity instanceof Earning) {
            throw new ModelCastException('Earning', get_class($entity));
        }

        // Assign the updates to the corresponding fields of the User instance
        // It ignores keys passed but not present in model columns
        foreach ($updates as $column => $value) {
            $entity->$column = $value;
        }

        // Save the updated Customer instance
        $entity->save();

        // Return the updated Customer model
        return $entity;
    }

    /**
     * @author @Intuneteq
     *
     * Calculate the balance (total earnings minus withdrawn earnings) for a given Earning entity.
     *
     * @param  Earning  $earning  The Earning entity for which to calculate the balance.
     * @return int The calculated balance.
     */
    public function getBalance(Earning $earning): int
    {
        return $earning->total_earnings - $earning->withdrawn_earnings;
    }
}
