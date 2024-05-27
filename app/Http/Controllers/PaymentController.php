<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ServerErrorException;
use App\Http\Requests\InitiateWithdrawalRequest;
use App\Http\Requests\PurchaseRequest;
use App\Http\Requests\StorePayOutRequest;
use App\Http\Requests\UpdatePayOutRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PayOutAccountResource;
use App\Http\Resources\PayoutResource;
use App\Models\PayOutAccount;
use App\Repositories\PaymentRepository;
use App\Repositories\PayoutRepository;
use App\Repositories\PaystackRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Storage;

class PaymentController extends Controller
{

    public function __construct(
        protected PaystackRepository $paystackRepository,
        protected PaymentRepository $paymentRepository,
        protected ProductRepository $productRepository,
        protected UserRepository $userRepository,
        protected PayoutRepository $payoutRepository,
    ) {
    }

    public function show()
    {
        $user = Auth::user();

        $payment = $user->payment;

        if (!$payment) {
            return new JsonResponse([
                'id' => null,
                'user_id' => null,
                'total_earnings' => null,
                'withdrawn_earnings' => null,
                'available_earnings' => null,
                'pending' => null,
            ]);
        }

        return new PaymentResource($user->payment);
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

        if ($paystack_customer && count($paystack_customer['subscriptions'])) {
            $status = $paystack_customer['subscriptions'][0]['status'];

            /**
             * The Customer has a subcription customer account with registered with us but not in our database.
             *
             * Check if the paystack subscription is active.
             *
             * Note, this part of the code might run on your local server due to formatting the db etc but not in production!
             *
             * If the status is not cancelled hence, active for whatever reason.
             */
            if ($status !== 'cancelled') {
                // Why is the database not updated to premium? Something must have gone wrong
                Log::channel('slack')->alert(
                    'USER HAS AN ACTIVE SUBSCRIPTION BUT IS NOT A PREMIUM USER IN DB',
                    ['context' => [
                        'email' => $user->email,
                        'paystack_customer_code' => $paystack_customer['customer_code']
                    ]]
                );

                /************** What went wrong? Check if the paystack table have the necessary columns. *****************/
                $paystack = $user->paystack;

                if (!$paystack) {
                    /************** what happened with the database? Maybe db was formatted? *****************/
                    Log::channel('slack')->alert(
                        'USER HAS AN ACTIVE SUBSCRIPTION BUT NO RECORD OF SUBSCRIPTION IN THE DB',
                        ['context' => [
                            'email' => $user->email,
                            'paystack_customer_code' => $paystack_customer['customer_code']
                        ]]
                    );
                }

                /********** Create a fresh paystack or update the table for the user and make things right!. *************/
                $this->paystackRepository->updateOrCreate($user->id, [
                    'customer_code' => $paystack_customer['customer_code'],
                    'subscription_code' => $paystack_customer['subscriptions'][0]['subscription_code']
                ]);

                /************** Ensure the user remains a premium user *****************/
                $this->userRepository->guardedUpdate($user->email, 'account_type', 'premium');

                throw new BadRequestException("Sorry, you can't perform this action. It appears you already have an active subscription plan.");
            }

            /** Subscription was cancelled so we Enable it */
            try {
                $response = $this->paystackRepository->enableSubscription($paystack_customer['subscriptions'][0]['subscription_code']);
                return new JsonResponse(['data' => $response['message']]);
            } catch (\Throwable $th) {
                throw new ServerErrorException($th->getMessage());
            }
        }

        /**
         * At this point, It is established that the user is a first time subscriber.
         *
         * Create customer.
         * Initilize subscription.
         * Update the subscription code.
         */

        // ADD A START DATE TO SUBSCRIPTION SO IT STARTS AFTER FREE TRIAL ENDS

        try {
            $customer = $this->paystackRepository->createCustomer($user);

            $customer_code = $customer['customer_code'];

            $this->paystackRepository->updateOrCreate($user->id, [
                'customer_code' => $customer_code,
            ]);

            $subscription = $this->paystackRepository->initializeTransaction($user->email, 5000, true);
            return new JsonResponse(['data' => $subscription]);
        } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
        }
    }

    public function enablePaystackSubscription()
    {
        $user = Auth::user();

        $paystack = $user->paystack;

        try {
            $subscription = $this->paystackRepository->enableSubscription($paystack->subscription_code);
            return new JsonResponse(['data' => $subscription]);
        } catch (\Exception $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    public function managePaystackSubscription()
    {
        $user = Auth::user();

        $paystack = $user->paystack;

        try {
            $response = $this->paystackRepository->manageSubscription($paystack->subscription_code);

            return new JsonResponse(['data' => $response]);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    public function cancelSubscription()
    {
        $user = Auth::user();

        $paystack = $user->paystack;

        try {
            $response = $this->paystackRepository->disableSubscription($paystack->subscription_code);

            return new JsonResponse(['data' => $response]);
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

    public function createPayOutAccount(StorePayOutRequest $request)
    {
        $user = Auth::user();

        $credentials = $request->validated();

        $account_number = $credentials['account_number'];

        $bank_code = $credentials['bank_code'];

        $name = $credentials['name'];

        $bank_name = $credentials['bank_name'];

        $account_exists = PayOutAccount::where('account_number', $account_number)->exists();

        /** Check for sub account */
        if ($account_exists) {
            throw new ConflictException('Duplicate Pay out Account');
        }

        $account_number_validated = $this->paystackRepository->validateAccountNumber($account_number, $bank_code);

        if (!$account_number_validated) throw new BadRequestException('Invalid Account Number');

        try {
            // Create a transfer recipient with paystack
            $response = $this->paystackRepository->createTransferRecipient($name, $account_number, $bank_code);

            $payout_credentials = [
                'user_id' => $user->id,
                'account_number' => $account_number,
                'paystack_recipient_code' => $response['recipient_code'],
                'name' => $name,
                'bank_code' => $bank_code,
                'bank_name' => $bank_name,
                'active' => 1 // Let it be the active account by default
            ];

            $payout_account = $this->paymentRepository->createPayOutAccount($payout_credentials);

            $this->userRepository->guardedUpdate($user->email, 'payout_setup_at', Carbon::now());

            return new PayOutAccountResource($payout_account);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    public function getAllPayOutAccounts()
    {
        $user = Auth::user();

        $payout_accounts = $user->payOutAccounts()->get();

        return PayOutAccountResource::collection($payout_accounts);
    }

    public function updatePayOutAccount(PayOutAccount $account, UpdatePayOutRequest $request)
    {
        $validated = $request->validated();

        $account->active = $validated['active'];

        $account->save();

        return new PayOutAccountResource($account);
    }

    public function purchase(PurchaseRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        // Extract the cart from the request
        $cart = $validated['products'];

        $products = Arr::map($cart, function ($item) {
            // Get Slug
            $slug = $item['product_slug'];

            // Find the product by slug
            $product = $this->productRepository->find(['slug' => $slug]);

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
            $deduction = $amount * $this->paymentRepository->getCommission();

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
                'products' => $products
            ]
        ];

        try {
            $response = $this->paystackRepository->initializePurchaseTransaction($payload);
            return new JsonResponse(['data' => $response]);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    /**
     * Ensure Paystack Account is a business account
     * https://support.paystack.com/hc/en-us/articles/360009881960-How-do-I-upgrade-from-a-Starter-Business-to-a-Registered-Business-on-Paystack
     */
    public function initiateWithdrawal(InitiateWithdrawalRequest $request)
    {
        $user = Auth::user();

        // Check the user for payout account
        $payout_account_exist = $user->hasPayoutSetup();

        if (!$payout_account_exist)
            throw new BadRequestException('You need to set up your payout account before requesting a payout.');

        $amount = $request->amount;

        $payment = $user->payment;

        if ($amount > $payment->getAvailableEarnings())
            throw new BadRequestException('Insufficient balance. You cannot withdraw more than your current balance.');

        $payout_account = $user->payOutAccounts()->where('active', true)->first();

        $reference = Str::uuid()->toString();

        try {
            $response = $this->paystackRepository->initiateTransfer(
                $amount,
                $payout_account->paystack_recipient_code,
                $reference
            );

            $payout_cred = [
                'status' => 'pending',
                'reference' => $reference,
                'paystack_transfer_code' => $response['transfer_code'],
                'pay_out_account_id' => $payout_account->id,
                'amount' => $amount
            ];

            $this->payoutRepository->create($payout_cred);

            return new JsonResponse(['data' => 'Withdrawal Initiated']);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), 500);
        }
    }

    public function getPayouts(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;

        $end_date = $request->end_date;

        $payouts = $this->payoutRepository->find($user, $start_date, $end_date)->paginate(5);

        return PayoutResource::collection($payouts);
    }

    public function downloadPayoutList(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;

        $end_date = $request->end_date;


        $payouts = $this->payoutRepository->find($user, $start_date, $end_date)->get();

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');

        $columns = array('Price', 'BankName', 'BankAccountNumber', 'Period', 'Status');

        $data = [];

        $data[] = $columns;

        $fileName = "payouts_$now.csv";

        foreach ($payouts as $payout) {
            $row['Price']  = $payout->amount;
            $row['BankName']  = $payout->payoutAccount->bank_name;
            $row['BankAccountNumber']  = $payout->payoutAccount->account_number;
            $row['Period']  = $payout->created_at;
            $row['Status']  = $payout->status;

            $data[] = array($row['Price'], $row['BankName'], $row['BankAccountNumber'], $row['Period']);
        }

        $csvContent = '';
        foreach ($data as $csvRow) {
            $csvContent .= implode(',', $csvRow) . "\n";
        }

        $filePath = 'csv/' . $fileName;

        // Store the CSV content in the storage/app/csv directory
        Storage::disk('local')->put($filePath, $csvContent);

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        // Return the response with the file from storage
        return response()->stream(function () use ($filePath) {
            readfile(storage_path('app/' . $filePath));
        }, 200, $headers);
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

        if ($user->account_type === 'free_trial') {
            $response['renewal_date'] = Carbon::parse($user->created_at)->addDays(30);
            return new JsonResponse($response);
        }

        if ($user->isSubscribed()) {

            $subscription_id = $user->paystack?->subscription_code;

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
        }

        return new JsonResponse($response);
    }
}
