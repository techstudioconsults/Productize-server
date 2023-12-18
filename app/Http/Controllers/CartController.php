<?php

namespace App\Http\Controllers;

use App\Exceptions\ConflictException;
use App\Models\Cart;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Resources\CartResource;
use Auth;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        $items = Cart::where('user_id', $user->id)->get();

        return CartResource::collection($items);
    }

    public function store(StoreCartRequest $request)
    {
        $user = Auth::user();

        $payload = $request->validated();

        $payload['user_id'] = $user->id;

        $exist = $user->carts()->where(['product_slug' => $payload['product_slug']])->first();

        if ($exist) throw new ConflictException('Item exist in cart');

        $cart = Cart::create($payload);

        return new CartResource($cart);
    }

    public function show(Cart $cart)
    {
        return new CartResource($cart);
    }

    public function update(UpdateCartRequest $request, Cart $cart)
    {
        $payload = $request->validated();

        $cart->quantity = $payload['quantity'];

        $cart->save();

        return new CartResource($cart);
    }

    public function delete(Cart $cart)
    {
        $cart->delete();

        return new JsonResponse([
            'message' => 'Item deleted'
        ]);
    }
}
