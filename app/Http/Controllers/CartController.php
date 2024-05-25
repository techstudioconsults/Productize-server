<?php

/**
 * @author Tobi Olanitori
 * @version 1.0
 * @since 25-05-2024
 */

namespace App\Http\Controllers;

use App\Exceptions\ConflictException;
use App\Models\Cart;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Resources\CartResource;
use Auth;
use Illuminate\Http\JsonResponse;

/**
 * Route handler methods for Cart resource
 */
class CartController extends Controller
{

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieves a paginated list of a user's carts.
     *
     * @return \App\Http\Resources\CartResource Returns a paginated collection of a user carts.
     */
    public function index()
    {
        $user = Auth::user();

        $items = Cart::where('user_id', $user->id)->get();

        return CartResource::collection($items);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Create a new cart.
     *
     * @param  \App\Http\Requests\StoreCartRequest  $request The incoming request containing validated cart data.
     * @return \App\Http\Resources\CartResource Returns a resource representing the newly created cart.
     */
    public function store(StoreCartRequest $request)
    {
        // Requesting user
        $user = Auth::user();

        // Retrieve validated payload
        $payload = $request->validated();

        // Add the user's id to the payload
        $payload['user_id'] = $user->id;

        // Check for ducplicate
        $exist = $user->carts()->where(['product_slug' => $payload['product_slug']])->first();

        // Throw exception, if duplicate
        if ($exist) throw new ConflictException('Item exist in cart');

        // Create cart
        $cart = Cart::create($payload);

        // Return response
        return new CartResource($cart);
    }

    /**
     * Retrive the specified product.
     *
     * @param  \App\Models\Cart  $cart The cart to display.
     * @return \App\Http\Resources\CartResource Returns a resource representing the queried cart.
     */
    public function show(Cart $cart)
    {
        return new CartResource($cart);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Update a given cart.
     *
     * @param  \App\Http\Requests\UpdateCartRequest  $request The incoming request containing validated product update data.
     * @param  \App\Models\Cart  $cart The cart to be updated.
     * @return \App\Http\Resources\ProductResource Returns a resource representing the newly updated cart.
     */
    public function update(UpdateCartRequest $request, Cart $cart)
    {
        $payload = $request->validated();

        $cart->quantity = $payload['quantity'];

        $cart->save();

        return new CartResource($cart);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Deelete a given cart.
     *
     * @param  \App\Models\Cart  $cart The cart to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a resource with a confirmation message.
     */
    public function delete(Cart $cart)
    {
        $cart->delete();

        return new JsonResponse([
            'message' => 'Item deleted'
        ]);
    }
}
