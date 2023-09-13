<?php

namespace App\Http\Controllers;

use App\Enums\PaymentGateway;
use App\Exceptions\ServerErrorException;
use App\Models\Payment;
use App\Repositories\PaystackRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Request;

class PaymentController extends Controller
{

    public function __construct(
        protected PaystackRepository $paystackRepository
    ) {
    }

    public function createSubscription(Request $request)
    {
        $paymentGatewayProvider = $request->query('provider');
        $user = Auth::user();


        $userPaymentProfile = $user->payments;
        $customer = null;
        $customer_code = null;
        $subscription = null;

        if ($paymentGatewayProvider === PaymentGateway::Paystack) {
            try {
                if (!$userPaymentProfile || !$userPaymentProfile->paystack_customer_code) {
                    $customer = $this->paystackRepository->createCustomer($user);
                    $customer_code = $customer['customer_code'];

                    Payment::create(['paystack_customer_code' => $customer_code, 'user_id' => $user->id]);
                } else {
                    $customer_code = $userPaymentProfile->paystack_customer_code;
                }

                $subscription = $this->paystackRepository->createSubscription($customer_code);
            } catch (\Throwable $th) {
                throw new ServerErrorException($th->getMessage());
            }

            $toUpdate = [
                'paystack_subscription_id' => $subscription['subscription_code']
            ];

            Payment::where('id', $userPaymentProfile->id)->update($toUpdate);
        }

        return new JsonResponse($subscription);
    }
}
