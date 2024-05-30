<?php

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Models\Payout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PayoutRepository extends Repository
{
    public function seed(): void
    {
    }

    public function create(array $credentials): Payout
    {
        $payout = new Payout();

        $payout->pay_out_account_id = $credentials['pay_out_account_id'];
        $payout->reference = $credentials['reference'];
        $payout->status = $credentials['status'];
        $payout->paystack_transfer_code = $credentials['paystack_transfer_code'];
        $payout->amount = $credentials['amount'];

        $payout->save();

        return $payout;
    }

    public function query(array $filter): Builder
    {
        $query = Payout::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    public function findById(string $id): ?Payout
    {
        return Payout::find($id);
    }

    public function findOne(array $filter): ?Payout
    {
        return Payout::where($filter)->firstOr(function () {
            return null;
        });
    }

    public function update(Model $entity, array $updates): Payout
    {
        // Ensure that the provided entity is an instance of Order
        if (!$entity instanceof Payout) {
            throw new ModelCastException("Payout", get_class($entity));
        }

        // Assign the updates to the corresponding fields of the payout instance
        $entity->fill($updates);

        // Save the updated payout instance
        $entity->save();

        // Return the updated payout model
        return $entity;
    }
}
