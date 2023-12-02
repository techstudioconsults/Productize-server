<?php

namespace App\Repositories;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackRepository
{
    public function __construct(
        protected PaymentRepository $paymentRepository,
        protected UserRepository $userRepository,
        protected CustomerRepository $customerRepository,
        protected OrderRepository $orderRepository,
    ) {
        $this->secret_key = config('payment.paystack.secret');
        $this->premium_plan_code = config('payment.paystack.plan_code');
        $this->client_url = config('app.client_url');
    }

    private $initializeTransactionUrl = "https://api.paystack.co/transaction/initialize";

    private $subscriptionEndpoint = "https://api.paystack.co/subscription";

    private $baseUrl = "https://api.paystack.co";

    private $secret_key;

    private $premium_plan_code;

    private $client_url;

    /**
     * Api Doc: https://paystack.com/docs/payments/webhooks/#ip-whitelisting
     * Paystack will only send webhook requests from their Ips
     */
    public $WhiteList = ['52.31.139.75', '52.49.173.169', '52.214.14.220'];

    /**
     * Create a plan on the dashboard - https://dashboard.paystack.com/#/plans
     * Subscription page for the plan - https://paystack.com/pay/lijv8w49sn
     *
     * Api Doc: https://paystack.com/docs/api/customer/
     */
    public function createCustomer(User $user)
    {
        $payload = [
            "email" => $user->email,
            "first_name" => $user->full_name,
            // "last_name" => "Sum",
            "phone" => $user->phone_number
        ];

        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . $this->secret_key,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/customer', $payload)->throw()->json();

        return $response['data'];
    }

    /**
     * https://paystack.com/docs/api/customer/#fetch
     */
    public function fetchCustomer(string $email)
    {
        $url = $this->baseUrl . "/customer/$email";

        try {
            $response = Http::withHeaders([
                "Authorization" => 'Bearer ' . $this->secret_key,
            ])->get($url)->throw()->json();

            return $response['data'];
        } catch (\Throwable $th) {
            $status_code = $th->getCode();

            if ($status_code === 404) {
                return null;
            } else {
                throw new ApiException($th->getMessage(), $status_code);
            }
        }
    }

    /**
     * Api Doc: https://paystack.com/docs/api/transaction/#initialize
     * Laravel Http: https://laravel.com/docs/10.x/http-client#main-content
     */
    public function initializeTransaction(string $email, int $amount, bool $isSubscription)
    {
        $payload = [
            'email' => $email,
            'amount' => $amount,
            "callback_url" => $this->client_url . '/dashboard'
        ];

        if ($isSubscription) {
            $payload['plan'] = $this->premium_plan_code;
        }

        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . $this->secret_key,
            "Cache-Control"  => "no-cache",
            'Content-Type' => 'application/json'
        ])->post($this->initializeTransactionUrl, $payload)->throw()->json();

        return $response['data'];
    }

    public function initializePurchaseTransaction(mixed $payload)
    {

        $payload = array_merge($payload, [
            "callback_url" => $this->client_url . '/dashboard'
        ]);

        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . $this->secret_key,
            "Cache-Control"  => "no-cache",
            'Content-Type' => 'application/json'
        ])->post($this->initializeTransactionUrl, $payload)->throw()->json();

        return $response['data'];
    }

    /**
     * Api Doc: https://paystack.com/docs/api/subscription#create
     * You can also pass a start_date parameter, which lets you set the date for the first debit incase of free trial.
     */
    public function createSubscription(string $customerId)
    {
        $payload = [
            "customer" => $customerId,
            "plan" => $this->premium_plan_code
        ];

        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . $this->secret_key,
            "Content-Type" => "application/json",
        ])->post($this->subscriptionEndpoint, $payload)->throw()->json();

        return $response['data'];
    }

    public function manageSubscription(string $subscriptionId)
    {
        $url = "{$this->baseUrl}/subscription/{$subscriptionId}/manage/link";

        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . $this->secret_key,
        ])->get($url);

        $data = json_decode($response->body(), true);

        if ($response->failed()) {
            Log::critical('Manage Paystack error', ['message' => $response]);
            $response->throw();
        }

        return $data['data'];
    }


    public function fetchSubscription($subscriptionId)
    {
        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . $this->secret_key,
        ])->get("{$this->baseUrl}/subscription/{$subscriptionId}");

        if ($response->successful()) {
            $data = json_decode($response->body(), true);

            return $data['data'];
        } else {
            Log::critical('Fetch subscription error', ['status' => $response->status()]);
        }
    }

    public function enableSubscription($subscriptionId)
    {
        $subscription = $this->fetchSubscription($subscriptionId);

        $payload = [
            "code" => $subscriptionId,
            "token" => $subscription['email_token']
        ];

        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . $this->secret_key,
            "Cache-Control"  => "no-cache",
            'Content-Type' => 'application/json'
        ])->post("{$this->baseUrl}/subscription/enable", $payload)->throw()->json();

        return $response['data'];
    }

    /**
     * Api Doc: https://paystack.com/docs/payments/subscriptions/#listen-for-subscription-events
     */
    public function webhookEvents(string $type, $data)
    {
        try {
            switch ($type) {
                case 'subscription.create':

                    // update sub code
                    $customer = $data['customer'];

                    $this->addUserPaymentSubscriptionCode(
                        $data['subscription_code'],
                        $customer['customer_code']
                    );

                    // update user to premium
                    $this->userRepository->guardedUpdate($customer['email'], 'account_type', 'premium');

                    break;

                case 'charge.success':
                    if ($data['split'] && count($data['split'])) {

                        $metadata = $data['metadata'];
                        $purchase_user_id = $metadata['purchase_user_id'];

                        // SaveCustomerOrder::dispatch(
                        //     $data['reference'],
                        //     $metadata,
                        //     $email
                        // );

                        try {
                            // Update user customer list for each product
                            foreach ($metadata['products'] as $product) {

                                // $product_slug =
                                $customer = $this->customerRepository->createOrUpdate($purchase_user_id, $product['product_slug']);

                                $buildOrder = [
                                    'reference_no' => $data['reference'],
                                    'product_id' => $customer->latest_puchase_id,
                                    'customer_id' => $customer->id,
                                    // Total amount

                                    // what defines new order
                                    // new order revenue?
                                ];

                                $this->orderRepository->create($buildOrder);
                            }
                        } catch (\Throwable $th) {
                            Log::channel('webhook')->critical('ERROR OCCURED', ['error' => $th->getMessage()]);
                        }
                    }

                    break;

                case 'subscription.not_renew':
                    # code...
                    break;

                case 'invoice.create':
                    # code...
                    break;

                case 'invoice.update':
                    # code...
                    break;

                    /**
                     * Cancelling a subscription will also trigger the following events:
                     */

                case 'invoice.payment_failed':
                    # code...
                    break;

                case 'subscription.disable':
                    $email = $data['customer']['email'];

                    $this->userRepository->guardedUpdate($email, 'account_type', 'free');
                    break;

                case 'subscription.expiring_cards':
                    /**
                     * Might want to reach out to customers
                     * https://paystack.com/docs/payments/subscriptions/#handling-subscription-payment-issues
                     */
                    break;
            }
        } catch (\Throwable $th) {
            Log::critical('paystack webhook error', ['error_message' => $th->getMessage()]);
        }
    }

    public function isValidPaystackWebhook($payload, $signature)
    {
        $computedSignature = hash_hmac('sha512', $payload, $this->secret_key);
        return $computedSignature === $signature;
    }

    /**
     *
     */
    public function createSubAcount(array $payload)
    {
        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . $this->secret_key,
            "Cache-Control"  => "no-cache",
            'Content-Type' => 'application/json'
        ])->post("{$this->baseUrl}/subaccount", $payload)->throw()->json();

        return $response['data'];
    }

    public function getBankList()
    {
        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . $this->secret_key,
        ])->get("{$this->baseUrl}/bank?country=nigeria");

        return $response['data'];
    }

    private function addUserPaymentSubscriptionCode(string $sub_code, string $customer_code)
    {
        $update = [
            'paystack_subscription_id' => $sub_code
        ];

        $this->paymentRepository->update('paystack_customer_code', $customer_code, $update);
    }
}
