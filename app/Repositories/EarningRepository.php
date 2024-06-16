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

class EarningRepository extends Repository
{
    public function seed(): void
    {
    }

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

    public function query(array $filter): Builder
    {
        $query = Earning::query();

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

    public function findById(string $id): ?Earning
    {
        return Earning::find($id);
    }

    public function findOne(array $filter): ?Earning
    {
        return Earning::where($filter)->firstOr(function () {
            return null;
        });
    }

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

    public function getBalance(Earning $earning): int
    {
        return $earning->total_earnings - $earning->withdrawn_earnings;
    }
}
