<?php

namespace App\Http\Controllers;

use App\Enums\PaymentGateway;
use App\Exceptions\ServerErrorException;
use App\Exceptions\UnAuthorizedException;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\PaystackRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            }
        } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
        }

        // initialize customer transaction as a first timer
        $subscription = $this->paystackRepository->initializeTransaction($user->email, 5000, true);

        /**
         * Return Authorization url to the client for payment.
         * Note that this is the user's first time payment with us so we need at least one authorization from them.
         */
        return new JsonResponse($subscription);
    }

    public function handlePaystackWebHook(Request $request)
    {
        // Take this out and implement policy
        $ipAddress = $request->ip();

        /**
         * Malicious user detected
         */
        if (!in_array($ipAddress, $this->paystackRepository->WhiteList)) {
            throw new UnAuthorizedException('Malicious Ip');
        }

        $body = $request->all();

        $this->paystackRepository->webhookEvents($body['event'], $body['data']);

        return response();
    }
}