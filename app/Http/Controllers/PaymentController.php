<?php

namespace App\Http\Controllers;

use App\Exceptions\ServerErrorException;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\PaystackRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{

    public function __construct(
        protected PaystackRepository $paystackRepository
    ) {
    }

    public function createPaystackSubscription()
    {
        // Authenticated user
        $user = Auth::user();

        $userPaymentProfile = User::find($user->id)->payment;

        $customer = null;
        $customer_code = null;
        $subscription = null;

        try {
            // First timer ? Create customer Anyways
            if (!$userPaymentProfile || !$userPaymentProfile->paystack_customer_code) {
                $customer = $this->paystackRepository->createCustomer($user);
                $customer_code = $customer['customer_code'];

                Payment::create(['paystack_customer_code' => $customer_code, 'user_id' => $user->id]);

                // initialize customer transaction as a first timer
                $subscription = $this->paystackRepository->initializeTransaction($user->email, 5000, true);
            } else {
                // Uppdate subscription
            }

        } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
        }



        /**
         * Return Authorization url to the client for payment.
         * Note that this is the user's first time payment with us so we need at least one authorization from them.
         */
        return new JsonResponse($subscription);
    }

    public function handlePaystackWebHook(Request $request)
    {
        Log::critical('webhook came in');

        $payload = $request->getContent();

        $paystackHeader = $request->header('x-paystack-signature');

        if ($this->paystackRepository->isValidPaystackWebhook($payload, $paystackHeader)) {
            $this->paystackRepository->webhookEvents($payload['event'], $payload['data']);

            return response();
        } else {
            Log::critical('message', ['error' => 'Invalid webhook signature']);
        }
    }
}
