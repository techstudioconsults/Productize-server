<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;

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
            "Authorization" => 'Bearer ' . env('STRIPE_SECRET_KEY'),
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/customer', $payload);

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
            $payload['plan'] = env('STRIPE_PREMIUM_PLAN_CODE');
        }

        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . env('STRIPE_SECRET_KEY'),
            "Cache-Control"  => "no-cache",
            'Content-Type' => 'application/json'
        ])->post($this->initializeTransactionUrl, $payload);

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
            "Authorization" => 'Bearer ' . env('STRIPE_SECRET_KEY'),
            "Content-Type" => "application/json",
        ])->post($this->subscriptionEndpoint, $payload);

        return $response;
    }

    /**
     * Api Doc: https://paystack.com/docs/payments/subscriptions/#listen-for-subscription-events
     */
    public function webhookEvents(string $type, $data)
    {
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
        }
    }
}

/**
 * Needs
 * 1. Invoice created templated.
 * 
 */
