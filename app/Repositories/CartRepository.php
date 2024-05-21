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
    }

    public function create(array $entity): Cart
    {
        return Cart::create($entity);
    }

    public function find(?array $filter = null): Builder
    {
        $query = Cart::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    public function findByRelation(Model $parent, ?array $filter): Relation
    {
        // Start with the base relation
        $carts = $parent?->carts();

        if (!$carts) {
            throw new ApiException(
                "Unable to retrieve related carts. The parent model does not have a defined 'carts' relationship.",
                500
            );
        }

        if (empty($filter)) return $carts;

        $this->applyDateFilters($carts, $filter);

        return $carts->where($filter);
    }

    public function findById(string $id): Cart
    {
        return Cart::find($id);
    }

    public function findOne(array $filter): Cart
    {
        return Cart::where($filter)->first();
    }

    public function update(Model $entity, array $updates): Cart
    {
        if (!$entity instanceof Cart) {
            throw new ModelCastException("Cart", get_class($entity));
        }

        // Assign the updates to the corresponding fields of the User instance
        $entity->fill($updates);

        // Save the updated Customer instance
        $entity->save();

        // Return the updated Customer model
        return $entity;
    }
}
