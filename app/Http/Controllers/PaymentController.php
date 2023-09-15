<?php

namespace App\Http\Controllers;

use App\Exceptions\BadRequestException;
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

    private function getUserPaymentInfo()
    {
        // Authenticated user
        $user = Auth::user();

        return ['user' => $user, 'userPaymentInfo' => User::find($user->id)->payment];
    }

    public function createPaystackSubscription()
    {
        $userPaymentProfile = $this->getUserPaymentInfo();

        $userPaymentInfo = $userPaymentProfile['userPaymentInfo'];
        $user = $userPaymentProfile['user'];

        $customer = null;
        $customer_code = null;
        $subscription = null;

        try {
            // First timer ? Create customer Anyways
            if (!$userPaymentInfo || !$userPaymentInfo->paystack_customer_code) {
                $customer = $this->paystackRepository->createCustomer($user);
                $customer_code = $customer['customer_code'];

                Payment::create(['paystack_customer_code' => $customer_code, 'user_id' => $user->id]);

                // initialize customer transaction as a first timer
                $subscription = $this->paystackRepository->initializeTransaction($user->email, 5000, true);
            } else {
                // Uppdate subscription
                throw new BadRequestException('user currently has a subscription plan');
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

    public function enablePaystackSubscription(Request $request)
    {
        ['userPaymentInfo' => $userPaymentInfo] = $this->getUserPaymentInfo();
        $subscriptionId = $userPaymentInfo->subscriptionId;

        $subscription = null;
var_dump('1');
        try {
            $subscription = $this->paystackRepository->enableSubscription($subscriptionId);
        } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
        }
        var_dump('2');
        return new JsonResponse(['SUB' => $subscription]);
    }

    public function handlePaystackWebHook(Request $request)
    {
        Log::critical('webhook came in', ['value' => 'test']);

        $payload = $request->getContent();

        $paystackHeader = $request->header('x-paystack-signature');

        try {
            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }

        if ($this->paystackRepository->isValidPaystackWebhook($payload, $paystackHeader)) {

            try {
                Log::critical('payload', ['value' => $payload]);

                $data = json_decode($payload, true);
                Log::critical('data', ['value' => $data['data']]);
                // Log::critical('event', ['value' => $payload['event']]);
                // $this->paystackRepository->webhookEvents($payload['event'], $payload['data']);

            } catch (\Throwable $th) {
                throw new ServerErrorException($th->getMessage());
            }

            return response('test', 200);
        } else {
            Log::critical('message', ['error' => 'Invalid webhook signature']);
        }
    }
}
