<?php

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Exceptions\ServerErrorException;
use App\Exceptions\UnprocessableException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 26-05-2024
 *
 * Repository for Order resource
 */
class OrderRepository extends Repository
{
    public function seed(): void
    {
        $user = User::factory()->create();

        // Create orders within the date range
        Order::factory()->count(3)->state([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
        ])->create([
            'created_at' => Carbon::create(2024, 3, 15, 0),
        ]);

        // Create an order outside the date range
        Order::factory()->create([
            'product_id' => Product::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::create(2024, 3, 21, 0),
        ]);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Create a new order.
     *
     * @param  array  $entity  The data for creating the order:
     *                         - reference_no: Paystack reference number
     *                         - product_id: The Product ID
     *                         - quantity: The order quantity
     *                         - total_amount: quantity * product price
     *                         - user_id: The user making the order
     * @return Order The newly created order instance.
     */
    public function create(array $array): Model
    {
        $rules = [
            'reference_no' => 'required|string',
            'user_id' => 'required|string',
            'total_amount' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
            'product_id' => 'required|string',
        ];

        if (! $this->isValidated($array, $rules)) {
            throw new ServerErrorException($this->getValidator()->errors()->first().' when calling order create');
        }

        $order = Order::create($array);

        return $order;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find orders based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found orders.
     */
    public function find(?array $filter = null): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a order by its ID.
     *
     * @param  string  $id  The ID of the order to find.
     * @return Order|null The found order instance, or null if not found.
     */
    public function findById(string $id): ?Order
    {
        return Order::find($id);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a single order based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Order|null The found cart instance, or null if not found.
     */
    public function findOne(array $filter): ?Order
    {
        return $this->query($filter)->first();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Query orders based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for orders.
     */
    public function query(array $filter): Builder
    {
        $query = Order::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Filter by product title
        if (isset($filter['product_title'])) {
            $product_title = $filter['product_title'];

            $query->whereHas('product', function (Builder $productQuery) use ($product_title) {
                $productQuery->where('title', 'like', '%'.$product_title.'%');
            });
        }

        // remove product title from the filter array
        unset($filter['product_title']);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Applies filters to an Eloquent relation.
     *
     * This method accepts an Eloquent relation and an array of filters. It applies the filters
     * to the relation, including date filters if they are present in the filter array.
     * If the filter array is empty, the original relation is returned.
     *
     * @param  Relation  $relation  The Eloquent relation to which the filters will be applied.
     * @param  array  $filter  An associative array of filters to apply to the relation.
     *                         Supported filters include:
     *                         - 'start_date' and 'end_date': Apply a date range filter on the 'created_at' column of the order table.
     *                         - Other key-value pairs will be used as where conditions on the relation.
     * @return Relation The filtered Eloquent relation.
     *
     * @throws UnprocessableException If the date range filter is invalid.
     */
    public function queryRelation(Relation $relation, array $filter): Relation
    {
        if (empty($filter)) {
            return $relation;
        }

        // Check for start_date and end_date in the array
        if (array_key_exists('start_date', $filter) && array_key_exists('end_date', $filter)) {
            $start_date = $filter['start_date'] ?? ''; // Possibly null
            $end_date = $filter['end_date'] ?? ''; // Possibly null

            // Remove them from the array
            unset($filter['start_date'], $filter['end_date']);
            $isInvalid = $this->isInValidDateRange($start_date, $end_date);

            if ($isInvalid) {
                throw new UnprocessableException($this->getValidator()->errors()->first());
            }

            if ($start_date && $end_date) {
                $relation->whereBetween('orders.created_at', [
                    Carbon::parse($start_date)->startOfDay(),
                    Carbon::parse($end_date)->endOfDay(),
                ]);
            }
        }

        if (isset($filter['product_title'])) {
            $product_title = $filter['product_title'];

            $relation->whereHas('product', function (Builder $query) use ($product_title) {
                $query->where('title', 'like', '%'.$product_title.'%');
            });
        }

        unset($filter['product_title']);

        $relation->where($filter);

        return $relation;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Update an entity in the database.
     *
     * @param  Model  $entity  The order to be updated
     * @param  array  $updates  The array of data containing the fields to be updated.
     * @return Model The updated order
     */
    public function update(Model $entity, array $updates): Order
    {
        // Ensure that the provided entity is an instance of Order
        if (! $entity instanceof Order) {
            throw new ModelCastException('Order', get_class($entity));
        }

        // Assign the updates to the corresponding fields of the Order instance
        $entity->fill($updates);

        // Save the updated Order instance
        $entity->save();

        // Return the updated Order model
        return $entity;
    }
}
