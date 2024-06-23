<?php

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Models\Payout;
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
 * Repository for Payout resource
 */
class PayoutRepository extends Repository
{

    /**
     * Create a new Payout instance and save it to the database.
     *
     * @param array $credentials The credentials array containing payout details:
     *                           - 'account_id' (int): The ID of the account associated with the payout.
     *                           - 'reference' (string): The reference code for the payout.
     *                           - 'status' (string): The status of the payout.
     *                           - 'paystack_transfer_code' (string): The Paystack transfer code for the payout.
     *                           - 'amount' (float): The amount of the payout.
     * @return \App\Models\Payout The created Payout object.
     */
    public function create(array $credentials): Payout
    {
        $payout = new Payout();

        $payout->account_id = $credentials['account_id'];
        $payout->reference = $credentials['reference'];
        $payout->status = $credentials['status'];
        $payout->paystack_transfer_code = $credentials['paystack_transfer_code'];
        $payout->amount = $credentials['amount'];

        $payout->save();

        return $payout;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Query payouts based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for payouts.
     */
    public function query(array $filter): Builder
    {
        $query = Payout::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find payouts based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found payouts.
     */
    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a payout by its ID.
     *
     * @param  string  $id  The ID of the payout to find.
     * @return Payout|null The found payout instance, or null if not found.
     */
    public function findById(string $id): ?Payout
    {
        return Payout::find($id);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a single payout based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Payout|null The found cart instance, or null if not found.
     */
    public function findOne(array $filter): ?Payout
    {
        return Payout::where($filter)->firstOr(function () {
            return null;
        });
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Update the fields of an existing Payout entity and save the changes.
     *
     * @param Payout $entity The Payout entity to update.
     * @param array $updates The array of updates containing field-value pairs to update.
     *
     * @return Payout The updated Payout entity.
     * @throws ModelCastException If $entity is not an instance of \App\Models\Payout.
     */
    public function update(Model $entity, array $updates): Payout
    {
        // Ensure that the provided entity is an instance of Payout
        if (!$entity instanceof Payout) {
            throw new ModelCastException('Payout', get_class($entity));
        }

        // Assign the updates to the corresponding fields of the payout instance
        $entity->fill($updates);

        // Save the updated payout instance
        $entity->save();

        // Return the updated payout model
        return $entity;
    }
}
