<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ServerErrorException;
use App\Http\Requests\PurchaseRequest;
use App\Http\Requests\UploadPayoutAccountRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Repositories\PaymentRepository;
use App\Repositories\PaystackRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{

    public function __construct(
        protected PaystackRepository $paystackRepository,
        protected PaymentRepository $paymentRepository,
        protected ProductRepository $productRepository,
        protected UserRepository $userRepository,
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



        // check if customer exist on paystack
        $paystack_customer = $this->paystackRepository->fetchCustomer($user->email);

        // check for active subscription
        if (count($paystack_customer['subscriptions']) && $paystack_customer['subscriptions'][0]['status'] === 'active') {

            // Everything is fine in paradise.
            if ($userPaymentInfo && $userPaymentInfo->paystack_customer_code && $userPaymentInfo->paystack_subscription_id) {
                throw new BadRequestException('user currently have a subscription plan');
            }

            // How come? We should have the customer code and subcriptionID already stored in the DB so this code should never run.
            Log::channel('slack')->alert('NO SUBSCRIPTION ID', ['context' => [
                'email' => $user->email,
                'paystack_customer_code' => $paystack_customer['customer_code']
            ]]);

            // Update subsciption code and customer code
            $this->paymentRepository->update('user_id', $user->id, [
                'paystack_customer_code' => $paystack_customer['customer_code'],
                'paystack_subscription_id' =>  $paystack_customer['subscriptions'][0]['subscription_code']
            ]);

            // Ensure the user remains a premium user
            $this->userRepository->guardedUpdate($user->email, 'account_type', 'premium');

            throw new BadRequestException('user currently have a subscription plan');
        }

        // First timer ? Create customer Anyways
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

                // Log::alert('data', ['value' => $data['data']]);
                // Log::alert('event', ['value' => $data['event']]);

                Log::channel('webhook')->info('data', ['value' => $data['data']]);
                Log::channel('webhook')->info('event', ['value' => $data['event']]);

                $this->paystackRepository->webhookEvents($data['event'], $data['data']);
            } catch (\Throwable $th) {
                throw new ServerErrorException($th->getMessage());
            }

            return response('webhook success', 200);
        } else {
            Log::critical('message', ['error' => 'Invalid webhook signature']);
        }
    }

    /**
     * set up user payout account
     */
    public function payOutAccount(UploadPayoutAccountRequest $request)
    {
        $user = Auth::user();

        $credentials = $request->validated();

        $payload = array_merge($credentials, ["percentage_charge" => 5]);

        /** Get user payment Info from DB */
        $payments = $this->getUserPaymentInfo()['userPaymentInfo'];

        /** Check for sub account */
        if ($payments->paystack_sub_account_code) {
            throw new BadRequestException('Sub Account Exist');
        }

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

    public function purchase(PurchaseRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        $products = $validated['products'];

        $sub_accounts = Arr::map($products, function ($obj) {
            $slug = $obj['product_slug'];

            $product = $this->productRepository->getProductBySlug($slug);

            $sub_account = $product->user->payment->paystack_sub_account_code;

            if (!$sub_account) throw new BadRequestException('Merchant Payout Account Not Found');

            $amount = $product->price * $obj['quantity'];

            $deduction = $amount * 0.2;

            $share = $product->price * $obj['quantity'] - $deduction;

            return [
                "subaccount" => $sub_account,
                "amount" => $amount,
                "share" => $share
            ];
        });

        $total_amount = array_reduce($sub_accounts, function ($carry, $item) {
            return $carry + ($item['amount']);
        }, 0);

        if ($total_amount !== $validated['amount']) {
            throw new BadRequestException('Total amount does not match quantity');
        }

        $metadata = json_encode(array_merge($validated, [
            'purchase_user_id' => $user->id,
        ]));

        $payload = [
            'email' => $user->email,
            'amount' => $total_amount,
            'split' => [
                'type' => 'flat',
                'bearer_type' => 'account',
                'subaccounts' => $sub_accounts
            ],
            'metadata' => $metadata,
        ];

        try {
            $response = $this->paystackRepository->initializePurchaseTransaction($payload);

            return $response;
        } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
        }
    }

    /**
     * Get a List of all banks supported by paystack
     * @return array - keys name, code
     */
    public function getBankList()
    {
        $banks = $this->paystackRepository->getBankList();

        $response = Arr::map($banks, function ($bank) {
            return [
                'name' => $bank['name'],
                'code' => $bank['code']
            ];
        });

        return new JsonResponse($response, 200);
    }
}
