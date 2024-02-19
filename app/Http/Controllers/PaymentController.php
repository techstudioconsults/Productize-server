<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ServerErrorException;
use App\Http\Requests\PurchaseRequest;
use App\Http\Requests\StoreSubAccountRequest;
use App\Http\Requests\UpdateSubAccountRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\SubaccountResource;
use App\Models\Payment;
use App\Models\Paystack;
use App\Models\Subaccounts;
use App\Models\User;
use App\Repositories\PaymentRepository;
use App\Repositories\PaystackRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
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

    public function createPaystackSubscription()
    {
        $user = Auth::user();

        // Is the user subscribed ?
        if ($user->isSubscribed()) {
            throw new BadRequestException("Sorry, you can't perform this action. It appears you already have an active subscription plan.");
        }

        // check if customer exist on paystack
        $paystack_customer = $this->paystackRepository->fetchCustomer($user->email);

        return new JsonResponse($paystack_customer);

        //============== THIS CODE SHOULD NEVER RUN, IF IT DOES, THERE IS PROBLEM THAT NEEDS INVESTIGATION =======================//

        /**
         *Check paystack for active subscription with the email.
         *
         * Note, it might run on your local server due to formatting the db etc but not in production!
         */
        if ($paystack_customer && count($paystack_customer['subscriptions']) && $paystack_customer['subscriptions'][0]['status'] === 'active') {
            // Why is the database not updated to premium? Something must have gone wrong
            Log::channel('slack')->alert(
                'USER HAS AN ACTIVE SUBSCRIPTION BUT IS NOT A PREMIUM USER IN DB',
                ['context' => [
                    'email' => $user->email,
                    'paystack_customer_code' => $paystack_customer['customer_code']
                ]]
            );

            // What went wrong? Check if the paystack table has the necessary columns.
            $paystack = $user->paystack;
            // $paystack = Paystack::firstWhere('user_id', $user->id);
            var_dump($paystack);

            if (!$paystack) {
                // what happened with the database? Maybe db was formatted?
                Log::channel('slack')->alert(
                    'USER HAS AN ACTIVE SUBSCRIPTION BUT NO RECORD OF SUBSCRIPTION IN THE DB',
                    ['context' => [
                        'email' => $user->email,
                        'paystack_customer_code' => $paystack_customer['customer_code']
                    ]]
                );

                // Create a fresh paystack table for the user and make things right!.
                $this->paystackRepository->updateOrCreate($user->id, [
                    'customer_code' => $paystack_customer['customer_code'],
                    'subscription_code' => $paystack_customer['subscriptions'][0]['subscription_code']
                ]);

                // Ensure the user remains a premium user
                $this->userRepository->guardedUpdate($user->email, 'account_type', 'premium');

                throw new BadRequestException("Sorry, you can't perform this action. It appears you already have an active subscription plan.");
            }
        }

        //========================================== END =======================================================//

        /**
         * At this point, user is not subscribed
         *
         * Two options:
         * 1. They were previously registered and canceled subscription
         * 2. This is their first time.
         */

         // I stopped here
         // I am trying to check if subscription status is non-renewing, attention, completed or cancelled. https://paystack.com/docs/payments/subscriptions/#managing-subscriptions
         // At this stage, you want to enable the subscription - https://paystack.com/docs/api/subscription/#enable

         // Option 1.
         if($paystack_customer && count($paystack_customer['subscriptions']) && $paystack_customer['subscriptions'][0]['status'] === 'complete'){
            // var_dump($pay);
            return new JsonResponse($paystack_customer);
         }

        return new JsonResponse();
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
        if ($paystack_customer && count($paystack_customer['subscriptions']) && $paystack_customer['subscriptions'][0]['status'] === 'active') {

            // Everything is fine in paradise.
            if ($userPaymentInfo && $userPaymentInfo->paystack_customer_code && $userPaymentInfo->paystack_subscription_id) {
                throw new BadRequestException('user currently have a subscription plan');
            }

            // How come? We should have the customer code and subcriptionID already stored in the DB so this code should never run.
            // Set up a problem log on slack for this
            Log::channel('slack')->alert('NO SUBSCRIPTION ID', ['context' => [
                'email' => $user->email,
                'paystack_customer_code' => $paystack_customer['customer_code']
            ]]);

            // Update subsciption code and customer code
            $this->paymentRepository->updateOrCreate($user->id, [
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

    public function enablePaystackSubscription()
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

    public function managePaystackSubscription()
    {
        $user = Auth::user();

        $subscriptionId = $user->payment->paystack_subscription_id;

        try {
            $response = $this->paystackRepository->manageSubscription($subscriptionId);

            return new PaymentResource($user->payment, $response);
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
    public function createSubAccount(StoreSubAccountRequest $request)
    {
        $user = Auth::user();

        $credentials = $request->validated();

        $payload = array_merge($credentials, [
            "percentage_charge" => 5,
            "primary_contact_email" => $user->email
        ]);

        $account_exists = Subaccounts::where('account_number', $credentials['account_number'])->exists();

        /** Check for sub account */
        if ($account_exists) {
            throw new BadRequestException('Sub Account Exist');
        }

        /**
         * Validate account number
         *
         * https://paystack.com/docs/identity-verification/verify-account-number/#resolve-account-number
         */

        /**
         * create transfer recipient
         * https://paystack.com/docs/transfers/creating-transfer-recipients/#create-recipient
         */

        /**
         * Now to initialize a transfer to customer payout account
         *
         * Create Transfer reference with uuid
         * https://paystack.com/docs/transfers/single-transfers/#generate-a-transfer-reference
         *
         * https://paystack.com/docs/transfers/managing-transfers/#server-approval
         */

        try {
            $paystack_sub_account = $this->paystackRepository->createSubAcount($payload);

            $paystack_sub_account_code = $paystack_sub_account['subaccount_code'];

            $sub_account_payload = array_merge($credentials, [
                'sub_account_code' => $paystack_sub_account_code,
                'user_id' => $user->id,
                'active' => 1
            ]);

            $sub_account = $this->paymentRepository->createSubAccount($sub_account_payload);

            $this->userRepository->guardedUpdate($user->email, 'payout_setup_at', Carbon::now());
        } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
        }

        return new SubaccountResource($sub_account);
    }

    public function getAllSubAccounts()
    {
        $user = Auth::user();

        $sub_accounts = $user->subaccounts()->get();

        return SubaccountResource::collection($sub_accounts);
    }

    public function updateSubaccount(Subaccounts $account, UpdateSubAccountRequest $request)
    {
        $validated = $request->validated();

        $account->active = $validated['active'];

        $account->save();

        return new SubaccountResource($account);
    }

    public function purchase(PurchaseRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        $products = $validated['products'];

        $sub_accounts = Arr::map($products, function ($obj) {
            $slug = $obj['product_slug'];

            $product = $this->productRepository->getProductBySlug($slug);

            $merchant = $product->user;

            if (!$product) throw new NotFoundException("Product with slug $slug not found");

            if (!$merchant->hasSubaccount())
                throw new BadRequestException("Merchant with user Id: $merchant->id Payout Account Not Found");

            $sub_account = $merchant->activeSubaccount()->sub_account_code;

            // Total Product Amount
            $amount = $product->price * $obj['quantity'];

            // Productize's %
            $deduction = $amount * 0.05;

            // Take it off total amount to sub account
            $share = $amount - $deduction;

            return [
                "subaccount" => $sub_account,
                "amount" => $amount,
                "share" => $share * 100 // Convert to naira. Paystack values at kobo
            ];
        });

        $total_amount = array_reduce($sub_accounts, function ($carry, $item) {
            return $carry + ($item['amount']);
        }, 0);

        if ($total_amount !== $validated['amount']) {
            throw new BadRequestException('Total amount does not match quantity');
        }

        $metadata = json_encode(array_merge($validated, [
            'buyer_id' => $user->id,
        ]));

        $payload = [
            'email' => $user->email,
            'amount' => $total_amount * 100,
            'split' => [
                'type' => 'flat',
                'bearer_type' => 'account',
                'subaccounts' => $sub_accounts
            ],
            'metadata' => $metadata
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

    public function billing()
    {
        $user = Auth::user();


        $response = [
            'renewal_date' => null,
            'plan' => $user->account_type,
            'billing_total' => null,
            'plans' => []
        ];

        $subscription_id = $user->payment?->paystack_subscription_id;

        if ($subscription_id) {
            $subscription = $this->paystackRepository->fetchSubscription($subscription_id);

            $plans = Arr::map($subscription['invoices'], function ($plan) {
                return [
                    'plan' => 'premium',
                    'price' => $plan['amount'] / 100,
                    'status' => $plan['status'],
                    'reference' => $plan['reference'],
                    'date' => $plan['createdAt'],
                ];
            });

            $response = [
                'renewal_date' => $subscription['next_payment_date'],
                'plan' => $user->account_type,
                'billing_total' => $subscription['amount'] / 100,
                'plans' => $plans
            ];
        }


        return new JsonResponse($response);
    }
}
