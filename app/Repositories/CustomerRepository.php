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
     */
    public function createOrUpdate(string $email, string $product_slug)
    {
        $product = $this->productRepository->getProductBySlug($product_slug);

        $customer = Customer::updateOrCreate(
            ['email' => $email],
            [
                'user_id' => $product->user_id,
                'latest_puchase_id' => $product->id
            ]
        );

        return $customer;
    }
}
