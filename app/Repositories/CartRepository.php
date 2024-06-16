<?php

/**
 * @author @Intuneteq Tobi Olanitori
 * @version 1.0
 * @since 21-05-2024
 */

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Models\Cart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * Repository for Cart resource
 */
class CartRepository extends Repository
{
    public function seed(): void
    {
        Cart::factory()->create()->count(10);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Create a new cart.
     *
     * @param array $entity The data for creating the cart.
     * @return Cart The newly created cart instance.
     */
    public function create(array $entity): Cart
    {
        return Cart::create($entity);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Query carts based on the provided filter.
     *
     * @param array $filter The filter criteria to apply.
     * @return Builder The query builder for carts.
     */
    public function query(array $filter): Builder
    {
        $query = Cart::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find carts based on the provided filter.
     *
     * @param array|null $filter The filter criteria to apply (optional).
     * @return Collection The collection of found carts.
     */
    public function find(?array $filter = null): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a cart by its ID.
     *
     * @param string $id The ID of the cart to find.
     * @return Cart|null The found cart instance, or null if not found.
     */
    public function findById(string $id): ?Cart
    {
        return Cart::find($id);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a single cart based on the provided filter.
     *
     * @param array $filter The filter criteria to apply.
     * @return Cart|null The found cart instance, or null if not found.
     */
    public function findOne(array $filter): ?Cart
    {
        return Cart::where($filter)->firstOr(function () {
            return null;
        });
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Update a cart entity with the provided updates.
     *
     * @param Model $entity The cart entity to update.
     * @param array $updates The updates to apply to the cart.
     * @return Cart The updated cart instance.
     * @throws ModelCastException If the provided entity is not a Cart instance.
     */
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

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Calculate the total amount for the given products in a cart.
     *
     * @param array $products Array of products with their details from the cart.
     * @return int The total amount calculated from the product amounts.
     */
    public function calculateTotalAmount(array $products)
    {
        return array_reduce($products, fn ($total_amount, $product) => $total_amount + $product['amount'], 0);
    }
}
