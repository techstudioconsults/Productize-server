<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Order;

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
        // $product = $this->productRepository->getProductBySlug($product_slug);

        // $customer = Customer::updateOrCreate(
        //     [
        //         'buyer_id' => $buyer_id,
        //         'product_owner_id' => $product->user_id,
        //     ],
        //     ['latest_puchase_id' => $product->id]
        // );

        // return $customer;
    }
}
