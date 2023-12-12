<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Resources\CartResource;
use Auth;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{

    public function store(StoreCartRequest $request)
    {
        $user = Auth::user();

        $payload = $request->validated();

        $cart = Cart::updateOrCreate(['user_id' => $user->id], $payload);

        return new CartResource($cart);
    }

    public function show()
    {
        $user = Auth::user();

        $cart = $user->cart;

        if (!$cart) {
            return new JsonResponse([]);
        }

        return new CartResource($cart);
    }
}
