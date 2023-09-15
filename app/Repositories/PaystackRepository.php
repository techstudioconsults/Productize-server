<?php

namespace App\Repositories;

use App\Exceptions\ServerErrorException;
use App\Models\Payment;
use App\Models\User;
use Error;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackRepository
{
    public function __construct(
        protected PaymentRepository $paymentRepository,
        protected UserRepository $userRepository
    ) {
    }

    private $initializeTransactionUrl = "https://api.paystack.co/transaction/initialize";

    private $subscriptionEndpoint = "https://api.paystack.co/subscription";

    private $baseUrl = "https://api.paystack.co";

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
            "Authorization" => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/customer', $payload)->throw()->json();

        return $response['data'];
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
            "callback_url" => env('CLIENT_URL') . '/dashboard'
        ];

        if ($isSubscription) {
            $payload['plan'] = env('PAYSTACK_PREMIUM_PLAN_CODE');
        }

        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
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
            "plan" => env('STRIPE_PREMIUM_PLAN_CODE')
        ];

        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
            "Content-Type" => "application/json",
        ])->post($this->subscriptionEndpoint, $payload)->throw()->json();

        return $response['data'];
    }

    public function manageSubscription(string $subscriptionId)
    {
        $url = "{$this->baseUrl}/subscription/{$subscriptionId}/manage/link";

        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
        ])->get($url);

        if ($response->successful()) {
            $data = json_decode($response->body(), true);
            return $data['data'];
        } else {
            Log::critical('Manage Paystack error', ['status' => $response->status()]);
        }
    }


    public function fetchSubscription($subscriptionId)
    {
        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
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
            "Authorization" => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
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

                    $subcription_code = $data['subscription_code'];
                    $customerCode = $data['customer']['customer_code'];

                    $update = [
                        'paystack_subscription_id' => $subcription_code
                    ];

                    $this->paymentRepository->update('paystack_customer_code', $customerCode, $update);
                    break;

                case 'charge.success':
                    $email = $data['customer']['email'];

                    $update = [
                        'account_type' => 'premium'
                    ];

                    $this->userRepository->update('email', $email, $update);
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

                    $update = [
                        'account_type' => 'free'
                    ];

                    $this->userRepository->update('email', $email, $update);
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
        $computedSignature = hash_hmac('sha512', $payload, env('PAYSTACK_SECRET_KEY'));
        return $computedSignature === $signature;
    }
}
