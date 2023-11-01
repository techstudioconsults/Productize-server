<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ServerErrorException;
use App\Http\Requests\UploadPayoutAccountRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Repositories\PaymentRepository;
use App\Repositories\PaystackRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{

    public function __construct(
        protected PaystackRepository $paystackRepository,
        protected PaymentRepository $paymentRepository
    ) {
    }

    private function getUserPaymentInfo()
    {
        // Authenticated user
        $user = Auth::user();

        return ['user' => $user, 'userPaymentInfo' => User::find($user->id)->payment];
    }

    public function createSubscription()
    {
        ['user' => $user, 'userPaymentInfo' => $userPaymentInfo] = $this->getUserPaymentInfo();

        $customer = null;
        $customer_code = null;
        $subscription = null;
        $payment = null;

        // First timer ? Create customer Anyways
        if (!$userPaymentInfo || !$userPaymentInfo->paystack_customer_code) {
            try {
                $customer = $this->paystackRepository->createCustomer($user);
                $customer_code = $customer['customer_code'];

                $payment = $this->paymentRepository->create(
                    ['paystack_customer_code' => $customer_code],
                    $user
                );

                // initialize customer transaction as a first timer
                $subscription = $this->paystackRepository->initializeTransaction($user->email, 5000, true);
            } catch (\Throwable $th) {
                throw new ServerErrorException($th->getMessage());
            }
        } else {
            // Uppdate subscription
            throw new BadRequestException('user currently have a subscription plan');
        }

        /**
         * Return Authorization url to the client for payment.
         * Note that this is the user's first time payment with us so we need at least one authorization from them.
         */
        return new PaymentResource($payment, $subscription);
    }

    public function enablePaystackSubscription(Payment $payment)
    {
        ['userPaymentInfo' => $userPaymentInfo] = $this->getUserPaymentInfo();
        $subscriptionId = $userPaymentInfo->paystack_subscription_id;

        try {
            $subscription = $this->paystackRepository->enableSubscription($subscriptionId);
            return new JsonResponse(['data' => $subscription]);
        } catch (\Exception $th) {
            throw new ServerErrorException($th->getMessage());
        }
    }

    public function managePaystackSubscription(Payment $payment)
    {
        $subscriptionId = $payment->paystack_subscription_id;

        try {
            $response = $this->paystackRepository->manageSubscription($subscriptionId);

            return new PaymentResource($payment, $response);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    public function handlePaystackWebHook(Request $request)
    {
        $payload = $request->getContent();

        $paystackHeader = $request->header('x-paystack-signature');

        if ($this->paystackRepository->isValidPaystackWebhook($payload, $paystackHeader)) {

            try {
                $data = json_decode($payload, true);

                Log::critical('data', ['value' => $data['data']]);
                Log::critical('event', ['value' => $data['event']]);

                $this->paystackRepository->webhookEvents($data['event'], $data['data']);
            } catch (\Throwable $th) {
                throw new ServerErrorException($th->getMessage());
            }

            return response('webhook success', 200);
        } else {
            Log::critical('message', ['error' => 'Invalid webhook signature']);
        }
    }

    public function payOutAccount(UploadPayoutAccountRequest $request)
    {
        $user = Auth::user();

        $credentials = $request->validated();

        $payload = array_merge($credentials, ["percentage_charge" => 18.2]);

        try {
            $paystack_sub_account = $this->paystackRepository->createSubAcount($payload);

            $paystack_sub_account_code = $paystack_sub_account['subaccount_code'];

            $updatables = array_merge($credentials, [
                'paystack_sub_account_code' => $paystack_sub_account_code
            ]);

            $this->paymentRepository->update('user_id', $user->id, $updatables);
        } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
        }

        return response('Account set up complete');
    }

    public function pay(Request $request)
    {
        $user = Auth::user();

        // $subaccounts =  {
        //     "subaccount": "ACCT_pwwualwty4nhq9d",
        //     "share": 6000
        //   },
        //   {
        //     "subaccount": "ACCT_hdl8abxl8drhrl3",
        //     "share": 4000
        //   },;

        $data = [
            'email' => $user->email,
            'amount' => 20000,
            'split' => [
                'type' => 'flat',
                'bearer_type' => 'account',
                'subaccounts' => [
                    []
                ]
            ],
            [
                'product_slug' => '',
                'quantity' => 5,
                'customer_id' => $user->id
            ]
        ];

        $payload = [];
        foreach ($data as $sale) {
            $product = Product::firstWhere('slug', $sale['slug']);

            $paystack_sub_account = $product->user->payment->paystack_sub_account_code;

            $share = $product->price * $sale['quanity'];

            array_push(
                []
            );
        }

        // for each product slug, get user payout account and product price
    }
}
