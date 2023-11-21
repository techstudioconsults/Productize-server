<?php

namespace App\Repositories;

use App\Models\Order;

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
}
