<?php

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 12-05-2024
 */

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Exceptions\ServerErrorException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * Repository for Customer resource
 */
class CustomerRepository extends Repository
{
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
                    'product_id' => $product->id,
                ]);

                foreach ($orders as $order) {
                    // Create a customer for each order
                    Customer::factory()->create([
                        'user_id' => $user->id,
                        'merchant_id' => $product->user_id,
                        'order_id' => $order->id,
                    ]);
                }
            }
        }
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Create a new customer.
     *
     * @param  array  $entity  The data for creating the customer.
     * @return Customer The newly created customer instance.
     */
    public function create(array $entity): Model
    {
        $rules = [
            'user_id' => 'required|string',
            'merchant_id' => 'required|string',
            'order_id' => 'required|string',
        ];

        if (! $this->isValidated($entity, $rules)) {
            throw new ServerErrorException($this->getValidator()->errors()->first().' when calling customer create');
        }

        return Customer::create($entity);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Query customers based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for customers.
     */
    public function query(array $filter): Builder
    {
        $query = Customer::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find customers based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found customers.
     */
    public function find(?array $filter = null): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a customer by its ID.
     *
     * @param  string  $id  The ID of the customer to find.
     * @return Customer|null The found customer instance, or null if not found.
     */
    public function findById(string $id): ?Customer
    {
        return Customer::find($id);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a single customer based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Customer|null The found customer instance, or null if not found.
     */
    public function findOne(array $filter): ?Customer
    {
        return $this->query($filter)->first();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Update an entity in the database.
     *
     * @param  Model  $entity  The customer to be updated
     * @param  array  $updates  The array of data containing the fields to be updated.
     * @return Model The updated customer
     */
    public function update(Model $entity, array $updates): Customer
    {
        // Ensure that the provided entity is an instance of Customer
        if (! $entity instanceof Customer) {
            throw new ModelCastException('Customer', get_class($entity));
        }

        // Assign the updates to the corresponding fields of the Customer instance
        $entity->fill($updates);

        // Save the updated Customer instance
        $entity->save();

        // Return the updated Customer model
        return $entity;
    }
}
