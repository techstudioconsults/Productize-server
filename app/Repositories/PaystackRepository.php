<?php

namespace App\Repositories;

use App\Dtos\BankDto;
use App\Dtos\CustomerDto;
use App\Dtos\SubscriptionDto;
use App\Dtos\TransactionInitializationDto;
use App\Dtos\TransferDto;
use App\Dtos\TransferRecipientDto;
use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @author @Intuneteq
 *
 * @version 1.0
 *
 * @since 13-09-2023
 *
 * Repository Handles Interactions with Paystack's APIs
 */
class PaystackRepository
{
    private $initializeTransactionUrl = 'https://api.paystack.co/transaction/initialize';

    private $subscriptionEndpoint = 'https://api.paystack.co/subscription';

    private $baseUrl = 'https://api.paystack.co';

    private $secret_key;

    private $premium_plan_code;

    private $client_url;

    /**
     * Paystack will only send webhook requests from these Ips
     *
     * @see https://paystack.com/docs/payments/webhooks/#ip-whitelisting
     */
    private $WhiteList = ['52.31.139.75', '52.49.173.169', '52.214.14.220'];


    /**
     * Constructor to initialize repositories and configuration.
     *
     * @param UserRepository $userRepository
     * @param CustomerRepository $customerRepository
     * @param OrderRepository $orderRepository
     * @param PayoutRepository $payoutRepository
     */
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

    /**
     * Create a new customer on Paystack.
     *
     * @param User $user
     *
     * @return CustomerDto
     *
     * @throws \Exception
     *
     * @see https://paystack.com/docs/api/customer/
     */
    public function createCustomer(User $user): CustomerDto
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
     * Fetch a customer from Paystack by email.
     *
     * @param string $email Customer's email
     *
     * @return CustomerDto|null
     *
     * @throws ApiException
     *
     * @see https://paystack.com/docs/api/customer/#fetch
     */
    public function fetchCustomer(string $email): ?CustomerDto
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
     * Initialize a transaction on Paystack.
     *
     * @param string $email User's email
     *
     * @param int $amount Transaction Amount
     *
     * @param bool $isSubscription True if it is a subscription transaction, false otherwise
     *
     * @return TransactionInitializationDto
     *
     * @throws \Exception
     *
     * @see https://paystack.com/docs/api/transaction/#initialize
     */
    public function initializeTransaction(string $email, int $amount, bool $isSubscription): TransactionInitializationDto
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

    /**
     * Initialize a purchase transaction on Paystack.
     *
     * @param mixed $payload
     *
     * @return TransactionInitializationDto
     *
     * @throws \Exception
     */
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
     * Create a subscription on Paystack.
     *
     * @param string $customerId The paystack cutomer id of the user
     *
     * @return SubscriptionDto
     *
     * @throws \Exception
     *
     * @see https://paystack.com/docs/api/subscription#create
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


    /**
     * Manage a subscription on Paystack.
     *
     * @param string $subscriptionId Paystack's subscription for the user
     *
     * @return array An associative array which includes a redirect link for the user to manage the subscription on paystack's UI
     *
     * @throws \Exception
     */
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

    /**
     * Fetch a subscription from Paystack.
     *
     * @param string $subscriptionId Paystack's subscription id of the user
     * @return SubscriptionDto|null
     *
     * @throws \Exception
     */
    public function fetchSubscription(string $subscriptionId): ?SubscriptionDto
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
        ])->get("{$this->baseUrl}/subscription/{$subscriptionId}");

        if ($response->successful()) {
            return SubscriptionDto::create($response['data']);
        } else {
            Log::critical('Fetch subscription error', ['status' => $response->status()]);

            return null;
        }
    }

    /**
     * Enable a subscription on Paystack.
     *
     * @param string $subscriptionId
     * @return array
     * @throws \Exception
     */
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

    /**
     * Disable a subscription on Paystack.
     *
     * @param string $subscription_code
     * @return array
     * @throws \Exception
     */
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

    /**
     * Validate a Paystack webhook.
     *
     * @param string $payload The Payload from Paystack
     *
     * @param string $signature The `x-paystack-signature` request header from paystack
     *
     * @return bool True when from paystack, false otherwise
     */
    public function isValidPaystackWebhook($payload, $signature): bool
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

    /**
     * Validate an account number with Paystack.
     *
     * @param string $account_number The Account Number
     *
     * @param string $bank_code Paystack Bank Code
     *
     * @return bool True when valid, false otherwise
     */
    public function validateAccountNumber(string $account_number, string $bank_code): bool
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
        ])->get("{$this->baseUrl}/bank/resolve?account_number=" . $account_number . '&bank_code=' . $bank_code);

        return $response['status'];
    }

    /**
     * Create a transfer recipient on Paystack.
     *
     * @param string $name Bank account name
     * @param string $account_number Bank account number
     * @param string $bank_code Paystack's bank code
     *
     * @return TransferRecipientDto
     *
     * @throws \Exception
     */
    public function createTransferRecipient($name, $account_number, $bank_code): TransferRecipientDto
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

        return TransferRecipientDto::create($response['data']);
    }

    /**
     * Initiate a transfer on Paystack.
     *
     * @param string $amount Transfer ammount
     * @param string $recipient_code The user's paystack recipient code
     * @param string $reference
     *
     * @return TransferDto
     *
     * @throws \Exception
     */
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

        return TransferDto::create($response['data']);
    }
}
