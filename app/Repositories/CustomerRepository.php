<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class CustomerRepository
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected UserRepository $userRepository,
    ) {
    }

    /**
     * Create a new customer for a user
     * @param buyer_id Customer's Id
     * @param product_slug Product slugified
     */
    public function createOrUpdate(string $buyer_id, string $product_slug)
    {
        $product = $this->productRepository->getProductBySlug($product_slug);

        $customer = Customer::updateOrCreate(
            [
                'buyer_id' => $buyer_id,
                'product_owner_id' => $product->user_id,
            ],
            ['latest_puchase_id' => $product->id]
        );

        return $customer;
    }
}
