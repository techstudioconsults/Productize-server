<?php

namespace App\Repositories;

use App\Exceptions\UnprocessableException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Validator;

class OrderRepository
{
    /**
     * array with params:
     * @param reference_no Paystack reference number
     * @param product_id Product ID
     * @param customer_id Customer ID
     */
    public function create(array $array)
    {
        $order = Order::create($array);

        return $order;
    }

    public function find(
        User $user,
        ?string $product_title = null,
        ?string $start_date = null,
        ?string $end_date = null
    ) {
        $orders = $user->orders();

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

            $orders->whereBetween('orders.created_at', [$start_date, $end_date]);
        }

        if ($product_title) {
            $orders->whereHas('product', function (Builder $query) use ($product_title) {
                $query->where('title', 'like', '%' . $product_title . '%');
            });
        }

        return $orders;
    }
}
