<?php

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 25-05-2024
 */

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Http\Requests\ClearCartRequest;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Mail\GiftAlert;
use App\Models\Cart;
use App\Repositories\CartRepository;
use App\Repositories\PaystackRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Auth;
use Illuminate\Http\JsonResponse;
use Mail;

/**
 * Route handler methods for Cart resource
 */
class CartController extends Controller
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected ProductRepository $productRepository,
        protected PaystackRepository $paystackRepository,
        protected UserRepository $userRepository,
    ) {
    }

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

        $carts = $this->cartRepository->find(['user_id' => $user->id]);

        return CartResource::collection($carts);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Create a new cart.
     *
     * @param  \App\Http\Requests\StoreCartRequest  $request  The incoming request containing validated cart data.
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

        // Validate cart exists
        $exists = $this->cartRepository->query(['product_slug' => $payload['product_slug']])->exists();

        // Throw exception, if duplicate
        if ($exists) {
            throw new ConflictException('Item exist in cart');
        }

        $cart = $this->cartRepository->create($payload);

        // Return response
        return new CartResource($cart);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrive the specified product.
     *
     * @param  \App\Models\Cart  $cart  The cart to display.
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
     * @param  \App\Http\Requests\UpdateCartRequest  $request  The incoming request containing validated product update data.
     * @param  \App\Models\Cart  $cart  The cart to be updated.
     * @return \App\Http\Resources\ProductResource Returns a resource representing the newly updated cart.
     */
    public function update(UpdateCartRequest $request, Cart $cart)
    {
        $cart = $this->cartRepository->update($cart, $request->validated());

        return new CartResource($cart);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Deelete a given cart.
     *
     * @param  \App\Models\Cart  $cart  The cart to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a resource with a confirmation message.
     */
    public function delete(Cart $cart)
    {
        $this->cartRepository->deleteOne($cart);

        return new JsonResponse([
            'message' => 'Item deleted',
        ]);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Clear the cart and initialize a purchase transaction.
     *
     * This method handles the process of clearing the user's cart, validating the request,
     * checking for gift user details, and initializing a purchase transaction using Paystack.
     *
     * @param  ClearCartRequest  $request  The request object containing validated cart data.
     * @return JsonResponse The response containing the Paystack transaction initialization data.
     *
     * @throws BadRequestException If the product is not found or not published,
     *                             or if the total amount does not match the quantity.
     * @throws ApiException If an error occurs during the Paystack transaction initialization.
     */
    public function clear(ClearCartRequest $request)
    {
        // Retrieve authenticated users
        $user = Auth::user();

        // Retrieve validated payload
        $validated = $request->validated();

        // If purchase is a gift to another user, retrieve the recipient's email, else instantiate to null
        $recipient_email = $validated['recipient_email'] ?? null;

        // If purchase is a gift to another user, retrieve the recipient's name, else instantiate to null
        $recipient_name = $validated['recipient_name'] ?? null;

        $recipient = null;

        if ($recipient_email) {
            $recipient = $this->userRepository->firstOrCreate($recipient_email, $recipient_name);

            // Send the Gift alert
            Mail::to($recipient)->send(new GiftAlert($recipient));
        }

        // Prepare products for the paystack transaction
        $products = $this->productRepository->prepareProducts($validated['products']);

        // Calculate the total amount for products in the cart
        $totalAmount = $this->cartRepository->calculateTotalAmount($products);

        // Validate that the total amount declared in the request payload matches that which was calculated
        if ($totalAmount !== $validated['amount']) {
            throw new BadRequestException('Total amount does not match quantity');
        }

        // Prepare paystack's payload
        $payload = [
            'email' => $user->email,
            'amount' => $totalAmount * 100,
            'metadata' => [
                'isPurchase' => true,
                'buyer_id' => $user->id,
                'products' => $products,
                'recipient_id' => $recipient ? $recipient->id : null,
            ],
        ];

        try {
            // Initialize payment
            $response = $this->paystackRepository->initializePurchaseTransaction($payload);

            return new JsonResponse(['data' => $response]);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }
}
