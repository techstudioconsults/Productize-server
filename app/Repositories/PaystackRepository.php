<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class PaystackRepository
{
    private $initializeTransactionUrl = "https://api.paystack.co/transaction/initialize";

    private $subscriptionEndpoint = "https://api.paystack.co/subscription";

    private $baseUrl = "https://api.paystack.co";

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
        ])->post($this->baseUrl.'/customer', $payload);

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
        ];

        if ($isSubscription) {
            $payload['plan'] = env('STRIPE_PREMIUM_PLAN_CODE');
        }

        $response = Http::withHeaders([
            "Authorization" => 'Bearer ' . env('STRIPE_SECRET_KEY'),
            "Cache-Control"  => "no-cache",
        ])->post($this->initializeTransactionUrl, $payload);

        return $response;
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

        return $response['data'];

        // {
        //     "status": true,
        //     "message": "Subscription successfully created",
        //     "data": {
        //       "customer": 1173,
        //       "plan": 28,
        //       "integration": 100032,
        //       "domain": "test",
        //       "start": 1459296064,
        //       "status": "active",
        //       "quantity": 1,
        //       "amount": 50000,
        //       "authorization": {
        //         "authorization_code": "AUTH_6tmt288t0o",
        //         "bin": "408408",
        //         "last4": "4081",
        //         "exp_month": "12",
        //         "exp_year": "2020",
        //         "channel": "card",
        //         "card_type": "visa visa",
        //         "bank": "TEST BANK",
        //         "country_code": "NG",
        //         "brand": "visa",
        //         "reusable": true,
        //         "signature": "SIG_uSYN4fv1adlAuoij8QXh",
        //         "account_name": "BoJack Horseman"
        //       },
        //       "subscription_code": "SUB_vsyqdmlzble3uii",
        //       "email_token": "d7gofp6yppn3qz7",
        //       "id": 9,
        //       "createdAt": "2016-03-30T00:01:04.687Z",
        //       "updatedAt": "2016-03-30T00:01:04.687Z"
        //     }
        //   }
    }

    /**
     * Api Doc: https://paystack.com/docs/payments/subscriptions/#listen-for-subscription-events
     */
    public function webhookEvents(string $eventType)
    {
        switch ($eventType) {
            case 'subscription.create':
                # code...
                break;

            case 'charge.success':
                # code...
                break;

            case 'invoice.create':
                # code...
                break;

            case 'invoice.update':
                # code...
                break;

            case 'invoice.payment_failed':
                # code...
                break;

                /**
                 * Cancelling a subscription will also trigger the following events:
                 */

            case 'invoice.payment_failed':
                # code...
                break;

            case 'subscription.disable':
                # code...
                break;

            default:
                # code...
                break;
        }
    }
}
