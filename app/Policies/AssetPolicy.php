<?php

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Asset;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AssetPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct() {}

    public function view(User $user, Asset $asset)
    {
        return $user->id === $asset->product->user->id
            ? Response::allow()
            : throw new ForbiddenException('Access Denied: No permission to access this resource');
    }

    public function viewByProduct(User $user, Product $product)
    {
        Response::allow();

        return $user->id === $product->user->id
            ? Response::allow()
            : throw new ForbiddenException('Access Denied: No permission to access this resource');
    }
}
