<?php

/**
 * @author @Intuneteq Tobi Olanitori
 * @version 1.0
 * @since 25-05-2024
 */

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Http\Requests\ClearCartRequest;
use App\Models\Cart;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Mail\GiftAlert;
use App\Repositories\CartRepository;
use App\Repositories\PaystackRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Arr;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

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

        // Validate cart exists
        $exists = $this->cartRepository->query(['product_slug' => $payload['product_slug']])->exists();

        // Throw exception, if duplicate
        if ($exists) throw new ConflictException('Item exist in cart');

        $cart = $this->cartRepository->create($payload);

        // Return response
        return new CartResource($cart);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
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
        $cart = $this->cartRepository->update($cart, $request->validated());

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
        $this->cartRepository->deleteOne($cart);

        return new JsonResponse([
            'message' => 'Item deleted'
        ]);
    }

    public function clear(ClearCartRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        $gift_email = $validated['gift_email'] ?? null;
        $gift_name = $validated['gift_name'] ?? null;

        $gift_user = null;

        if ($gift_email) {
            $query = $this->userRepository->query(['email' => $gift_email]);

            if ($query->exists()) {
                $gift_user = $query->first();
            } else {
                $gift_user = $this->userRepository->create([
                    'email' => $gift_email,
                    'full_name' => $gift_name
                ]);

                // send login email
                Mail::to($gift_user)->send(new GiftAlert($gift_user));
            }
        }

        // Extract the cart from the request
        $cart = $validated['products'];

        $products = Arr::map($cart, function ($item) {
            // Get Slug
            $slug = $item['product_slug'];

            // Find the product by slug
            $product = $this->productRepository->findOne(['slug' => $slug]);

            // Product Not Found, Cannot continue with payment.
            if (!$product) {
                throw new BadRequestException('Product with slug ' . $slug . ' not found');
            }

            if ($product->status !== 'published') {
                throw new BadRequestException('Product with slug ' . $slug . ' not published');
            }

            // Total Product Amount
            $amount = $product->price * $item['quantity'];

            // Productize's %
            $deduction = $amount * 0.05;

            // This is what the product owner will earn from this sale.
            $share = $amount - $deduction;

            return [
                "product_id" => $product->id,
                "amount" => $amount,
                "quantity" => $item['quantity'],
                "share" => $share
            ];
        });

        // Calculate Total Amount
        $total_amount = array_reduce($products, function ($carry, $item) {
            return $carry + ($item['amount']);
        }, 0);

        // Validate Total amount match that stated in request.
        if ($total_amount !== $validated['amount']) {
            throw new BadRequestException('Total amount does not match quantity');
        }

        $payload = [
            'email' => $user->email,
            'amount' => $total_amount * 100,
            'metadata' => [
                'isPurchase' => true, // Use this to filter the type of charge when handling the webhook
                'buyer_id' => $user->id,
                'products' => $products,
                'gift_user_id' => $gift_user ? $gift_user->id : null
            ]
        ];

        try {
            $response = $this->paystackRepository->initializePurchaseTransaction($payload);
            return new JsonResponse(['data' => $response]);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    // public function clear(ClearCartRequest $request)
    // {
    //     $user = Auth::user();
    //     $validated = $request->validated();

    //     $recipientEmail = $validated['recipient_email'] ?? null;
    //     $recipientName = $validated['recipient_name'] ?? null;

    //     $recipient = $this->handleRecipient($recipientEmail, $recipientName);

    //     $products = $this->processCart($validated['products']);
    //     $totalAmount = $this->calculateTotalAmount($products);

    //     if ($totalAmount !== $validated['amount']) {
    //         throw new BadRequestException('Total amount does not match quantity');
    //     }

    //     $payload = $this->preparePayload($user, $totalAmount, $products, $recipient);

    //     return $this->initializeTransaction($payload);
    // }

    // private function handleRecipient($recipientEmail, $recipientName)
    // {
    //     if (!$recipientEmail) {
    //         return null;
    //     }

    //     $recipient = $this->userRepository->query(['email' => $recipientEmail])->first();

    //     if (!$recipient) {
    //         $recipient = $this->userRepository->create([
    //             'email' => $recipientEmail,
    //             'full_name' => $recipientName
    //         ]);

    //         Mail::to($recipient)->send(new GiftAlert($recipient));
    //     }

    //     return $recipient;
    // }

    // private function processCart(array $cart)
    // {
    //     return Arr::map($cart, function ($item) {
    //         $slug = $item['product_slug'];
    //         $product = $this->productRepository->findOne(['slug' => $slug]);

    //         if (!$product) {
    //             throw new BadRequestException('Product with slug ' . $slug . ' not found');
    //         }

    //         if ($product->status !== 'published') {
    //             throw new BadRequestException('Product with slug ' . $slug . ' not published');
    //         }

    //         $amount = $product->price * $item['quantity'];
    //         $share = $amount - ($amount * 0.05);

    //         return [
    //             'product_id' => $product->id,
    //             'amount' => $amount,
    //             'quantity' => $item['quantity'],
    //             'share' => $share
    //         ];
    //     });
    // }

    // private function calculateTotalAmount(array $products)
    // {
    //     return array_reduce($products, function ($carry, $item) {
    //         return $carry + $item['amount'];
    //     }, 0);
    // }

    // private function preparePayload($user, $totalAmount, $products, $recipient)
    // {
    //     return [
    //         'email' => $user->email,
    //         'amount' => $totalAmount * 100,
    //         'metadata' => [
    //             'isPurchase' => true,
    //             'buyer_id' => $user->id,
    //             'products' => $products,
    //             'recipient_id' => $recipient ? $recipient->id : null
    //         ]
    //     ];
    // }

    // private function initializeTransaction(array $payload)
    // {
    //     try {
    //         $response = $this->paystackRepository->initializePurchaseTransaction($payload);
    //         return new JsonResponse(['data' => $response]);
    //     } catch (\Throwable $th) {
    //         throw new ApiException($th->getMessage(), $th->getCode());
    //     }
    // }
}
