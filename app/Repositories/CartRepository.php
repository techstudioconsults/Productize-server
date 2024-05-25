<?php

/**
 * @author Tobi Olanitori
 * @version 1.0
 * @since 21-05-2024
 */

namespace App\Repositories;

use App\Exceptions\ApiException;
use App\Exceptions\ModelCastException;
use App\Models\Cart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @author Tobi Olanitori
 *
 * Repository for Cart
 */
class CartRepository extends Repository
{
    public function seed(): void
    {
        Cart::factory()->create()->count(10);
    }

    /**
     * Create a new cart.
     * @param array $entity
     */
    public function create(array $entity): Cart
    {
        return Cart::create($entity);
    }

    public function query(array $filter): Builder
    {
        $query = Cart::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    public function find(?array $filter = null): Collection
    {
        return $this->query($filter ?? [])->get();
    }

    public function findById(string $id): Cart | null
    {
        return Cart::find($id);
    }

    public function findOne(array $filter): Cart | null
    {
        return $this->query($filter)->first();
    }

    public function update(Model $entity, array $updates): Cart
    {
        if (!$entity instanceof Cart) {
            throw new ModelCastException("Cart", get_class($entity));
        }

        // Assign the updates to the corresponding fields of the User instance
        // It ignores keys passed but not present in model columns
        $entity->fill($updates);

        // Save the updated Customer instance
        $entity->save();

        // Return the updated Customer model
        return $entity;
    }
}
