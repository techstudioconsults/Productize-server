<?php

namespace App\Repositories;

use App\Exceptions\ApiException;
use App\Exceptions\UnprocessableException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;

class CustomerRepository extends Repository
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected UserRepository $userRepository,
    ) {
    }

    public function seed(): void
    {
        // Create 5 users
        $users = User::factory(5)->create();

        foreach ($users as $user) {
            // Create 5 products for each user
            $products = Product::factory(5)->create(['user_id' => $user->id]);

            foreach ($products as $product) {
                // Create 5 orders for each product
                $orders = Order::factory(5)->create([
                    'user_id' => $user->id,
                    'product_id' => $product->id
                ]);

                foreach ($orders as $order) {
                    // Create a customer for each order
                    Customer::factory()->create([
                        'user_id' => $user->id,
                        'merchant_id' => $product->user_id,
                        'order_id' => $order->id
                    ]);
                }
            }
        }
    }

    public function create(array $entity): Model
    {
        return Customer::create($entity);
    }

    public function find(?array $filter = null): Builder
    {
        // Start with the base query
        $query = Customer::query();

        // Apply filters if provided
        if ($filter !== null) {
            // Check if start_date and end_date are provided in the filter array
            if (isset($filter['start_date']) && isset($filter['end_date'])) {
                $query->whereBetween('created_at', [$filter['start_date'], $filter['end_date']]);
            }

            foreach ($filter as $key => $value) {
                // Check if the key exists as a column in the orders table
                if (Schema::hasColumn('customers', $key)) {
                    // Apply where condition based on the filter
                    $query->where($key, $value);
                }
            }
        }

        return $query;
    }

    public function findById(string $id): Model
    {
        return Customer::find($id);
    }

    public function findByRelation(Model $parent, ?array $filter = null): Relation
    {
        // Start with the base relation
        $relation = $parent?->customers();

        if (!$relation) throw new ApiException("Error", 500);

        if (empty($filter)) return $relation;

        // If there are filters provided, apply them
        if (isset($filter['start_date']) && isset($filter['end_date'])) {
            $start_date = $filter['start_date'];
            unset($filter['start_date']);

            $end_date = $filter['end_date'];
            unset($filter['end_date']);

            $isInvalid = $this->isInValidDateRange($start_date, $end_date);

            if ($isInvalid) throw new UnprocessableException($this->getValidator()->errors()->first());

            $relation->whereBetween('created_at', [$start_date, $end_date]);
        }

        foreach ($filter as $key => $value) {
            // Check if the key exists as a column in the orders table
            if (Schema::hasColumn('customers', $key)) {
                // Apply where condition based on the filter
                $relation = $relation->where($key, $value);
            }
        }

        return $relation;
    }

    /**
     * Update an entity in the database.
     *
     * @param  Model $entity The model to be updated
     * @param array $updates The array of data containing the fields to be updated.
     * @return Model The updated model
     */
    public function update(Model $entity, array $updates): Model
    {
        // Ensure that the provided entity is an instance of Customer
        if (!$entity instanceof Customer) {
            throw new ApiException("Invalid Model", 500);
        }

        // Assign the updates to the corresponding fields of the Customer instance
        $entity->fill($updates);

        // Save the updated Customer instance
        $entity->save();

        // Return the updated Customer model
        return $entity;
    }

    public function updateMany(array $filter, array $updates): int
    {
        return 1;
    }


    public function deleteMany(array $filter): int
    {
        return 1;
    }
}
