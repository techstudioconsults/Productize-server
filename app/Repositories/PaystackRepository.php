<?php

namespace App\Repositories;

use App\Dtos\BankDto;
use App\Dtos\CustomerDto;
use App\Dtos\SubscriptionDto;
use App\Dtos\TransactionInitializationDto;
use App\Exceptions\ApiException;
use App\Models\Paystack;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackRepository
{
    public function __construct(
        protected UserRepository $userRepository,
        protected CustomerRepository $customerRepository,
        protected OrderRepository $orderRepository,
        protected PayoutRepository $payoutRepository,
    ) {
        $this->secret_key = config('payment.paystack.secret');
        $this->premium_plan_code = config('payment.paystack.plan_code');
        $this->client_url = config('app.client_url');
    }

    private $initializeTransactionUrl = 'https://api.paystack.co/transaction/initialize';

    private $subscriptionEndpoint = 'https://api.paystack.co/subscription';

    private $baseUrl = 'https://api.paystack.co';

    private $secret_key;

    private $premium_plan_code;

    private $client_url;

    /**
     * Api Doc: https://paystack.com/docs/payments/webhooks/#ip-whitelisting
     * Paystack will only send webhook requests from their Ips
     */
    private $WhiteList = ['52.31.139.75', '52.49.173.169', '52.214.14.220'];

    /**
     * Create a plan on the dashboard - https://dashboard.paystack.com/#/plans
     * Subscription page for the plan - https://paystack.com/pay/lijv8w49sn
     *
     * Api Doc: https://paystack.com/docs/api/customer/
     */
    public function createCustomer(User $user)
    {
        $full_name = explode(' ', trim($user->full_name));
        $payload = [
            'email' => $user->email,
            'first_name' => $full_name[0],
            "last_name" => end($full_name),
            'phone' => $user->phone_number,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/customer', $payload)->throw()->json();

        return CustomerDto::create($response['data']);
    }

    /**
     * https://paystack.com/docs/api/customer/#fetch
     */
    public function fetchCustomer(string $email)
    {
        $url = $this->baseUrl . "/customer/$email";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secret_key,
            ])->get($url)->throw()->json();

            return CustomerDto::create($response['data']);
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
            'callback_url' => $this->client_url . '/dashboard/home',
        ];

        if ($isSubscription) {
            $payload['plan'] = $this->premium_plan_code;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
        ])->post($this->initializeTransactionUrl, $payload)->throw()->json();

        return TransactionInitializationDto::create($response['data']);
    }

    public function initializePurchaseTransaction(mixed $payload)
    {

        $payload = array_merge($payload, [
            'callback_url' => $this->client_url . '/dashboard/downloads#all-downloads',
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
        ])->post($this->initializeTransactionUrl, $payload)->throw()->json();

        return TransactionInitializationDto::create($response['data']);
    }

    /**
     * Api Doc: https://paystack.com/docs/api/subscription#create
     * You can also pass a start_date parameter, which lets you set the date for the first debit incase of free trial.
     */
    public function createSubscription(string $customerId)
    {
        $payload = [
            'customer' => $customerId,
            'plan' => $this->premium_plan_code,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Content-Type' => 'application/json',
        ])->post($this->subscriptionEndpoint, $payload)->throw()->json();

        return SubscriptionDto::create($response['data']);
    }

    public function manageSubscription(string $subscriptionId)
    {
        $url = "{$this->baseUrl}/subscription/{$subscriptionId}/manage/link";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
        ])->get($url);

        $data = json_decode($response->body(), true);

        if ($response->failed()) {
            Log::critical('Manage Paystack error', ['message' => $response]);
            $response->throw();
        }

        // ["link" => ""]
        return $data['data'];
    }

    public function fetchSubscription(string $subscriptionId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
        ])->get("{$this->baseUrl}/subscription/{$subscriptionId}");

        if ($response->successful()) {
            $data = json_decode($response->body(), true);

            return SubscriptionDto::create($response['data']);
        } else {
            Log::critical('Fetch subscription error', ['status' => $response->status()]);
        }
    }

    public function enableSubscription(string $subscriptionId)
    {
        $subscription = $this->fetchSubscription($subscriptionId);

        $payload = [
            'code' => $subscriptionId,
            'token' => $subscription['email_token'],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/subscription/enable", $payload)->throw()->json();

        return $response['data'];
    }

    public function disableSubscription(string $subscription_code)
    {
        $subscription = $this->fetchSubscription($subscription_code);

        $payload = [
            'code' => $subscription_code,
            'token' => $subscription['email_token'],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/subscription/disable", $payload)->throw()->json();

        return $response['data'];
    }

    public function isValidPaystackWebhook($payload, $signature)
    {
        $computedSignature = hash_hmac('sha512', $payload, $this->secret_key);

        return $computedSignature === $signature;
    }

    /**
     * Retrive Bank List from Paystack
     *
     * @return Collection<int, BankDto>
     */
    public function getBankList(): Collection
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
        ])->get("{$this->baseUrl}/bank?country=nigeria");

        return collect($response['data'])->map(function ($data) {
            return BankDto::create($data);
        });
    }

    public function validateAccountNumber(string $account_number, string $bank_code)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
        ])->get("{$this->baseUrl}/bank/resolve?account_number=" . $account_number . '&bank_code=' . $bank_code);

        return $response['status'];
    }

    public function createTransferRecipient($name, $account_number, $bank_code)
    {
        $payload = [
            'type' => 'nuban',
            'name' => $name,
            'account_number' => $account_number,
            'bank_code' => $bank_code,
            'currency' => 'NGN',
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/transferrecipient", $payload)->throw()->json();

        return $response['data'];
    }

    public function initiateTransfer(string $amount, string $recipient_code, string $reference)
    {
        $payload = [
            'source' => 'balance',
            'reason' => 'Payout',
            'amount' => $amount,
            'recipient' => $recipient_code,
            'reference' => $reference,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/transfer", $payload)->throw()->json();

        return $response['data'];
    }
}
