<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public function create(array $credentials)
    {
        return Product::create($credentials);
    }
}
