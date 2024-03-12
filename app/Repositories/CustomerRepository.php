<?php

namespace App\Repositories;

use App\Exceptions\UnprocessableException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CustomerRepository
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected UserRepository $userRepository,
    ) {
    }

    /**
     * Create a new customer for a user
     * @param order Order made by the customer
     */
    public function create(Order $order)
    {
        $customer = new Customer();

        $customer->user_id = $order->user->id;

        $customer->merchant_id = $order->product->user->id;

        $customer->order_id = $order->id;

        $customer->save();

        return $customer;
    }

    public function find(
        User $user,
        ?string $start_date = null,
        ?string $end_date = null
    ) {
        $customers = $user->customers();

        if ($start_date && $end_date) {
            $validator = Validator::make([
                'start_date' => $start_date,
                'end_date' => $end_date
            ], [
                'start_date' => 'date',
                'end_date' => 'date'
            ]);

            if ($validator->fails()) {
                throw new UnprocessableException($validator->errors()->first());
            }

            $customers->whereBetween('created_at', [$start_date, $end_date]);
        }

        return $customers;
    }
}
