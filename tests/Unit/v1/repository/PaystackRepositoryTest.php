<?php

namespace Tests\Unit\v1\repository;

use App\Dtos\BankDto;
use App\Dtos\CustomerDto;
use App\Dtos\SubscriptionDto;
use App\Dtos\TransactionInitializationDto;
use App\Dtos\TransferDto;
use App\Dtos\TransferRecipientDto;
use App\Exceptions\ApiException;
use App\Exceptions\ServerErrorException;
use App\Models\User;
use App\Repositories\PaystackRepository;
use Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

class PaystackRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected PaystackRepository $paystackRepository;

    public function setUp(): void
    {
        parent::setUp();

        // Create an instance of the repository
        $this->paystackRepository = new PaystackRepository();
    }

    public function test_CreateCustomer_success()
    {
        $user = new User();

        $user->full_name = 'John Doe';
        $user->email = 'john.doe@example.com';
        $user->phone_number = '1234567890';

        $response_data = [
            'status' => true,
            'data' => [
                'id' => '12345',
                'email' => 'john.doe@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'customer_code' => 'CUS_bslevty3j70vk62',
                'subscriptions' => [],
                'createdAt' => '12-03-2024',
            ],
        ];

        Http::fake([
            'https://api.paystack.co/customer' => Http::response($response_data, 200),
        ]);

        $customerDto = $this->paystackRepository->createCustomer($user);

        $this->assertInstanceOf(CustomerDto::class, $customerDto);
        $this->assertEquals('12345', $customerDto->getId());
        $this->assertEquals('john.doe@example.com', $customerDto->getEmail());
        $this->assertEquals('John', $customerDto->getFirstName());
        $this->assertEquals('Doe', $customerDto->getLastName());
        $this->assertEquals('CUS_bslevty3j70vk62', $customerDto->getCode());
    }

    public function test_CreateCustomer_failure()
    {
        $this->expectException(RequestException::class);

        $user = new User();
        $user->full_name = 'Jane Doe';
        $user->email = 'jane.doe@example.com';
        $user->phone_number = '0987654321';

        Http::fake([
            'https://api.paystack.co/customer' => Http::response([], 500),
        ]);

        $this->paystackRepository->createCustomer($user);
    }

    public function test_fetchCustomer_success()
    {
        $email = 'john.doe@example.com';

        $response_data = [
            'status' => true,
            'data' => [
                'id' => '12345',
                'email' => $email,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'customer_code' => 'CUS_bslevty3j70vk62',
                'subscriptions' => [],
                'createdAt' => '12-03-2024',
            ],
        ];

        Http::fake([
            "https://api.paystack.co/customer/$email" => Http::response($response_data, 200),
        ]);

        $customerDto = $this->paystackRepository->fetchCustomer($email);

        $this->assertInstanceOf(CustomerDto::class, $customerDto);
        $this->assertEquals('12345', $customerDto->getId());
        $this->assertEquals($email, $customerDto->getEmail());
        $this->assertEquals('John', $customerDto->getFirstName());
        $this->assertEquals('Doe', $customerDto->getLastName());
        $this->assertEquals('CUS_bslevty3j70vk62', $customerDto->getCode());
    }

    public function test_fetchCustomer_NotFound()
    {
        $email = 'john.doe@example.com';

        Http::fake([
            'https://api.paystack.co/customer/*' => Http::response([], 404),
        ]);

        $customerDto = $this->paystackRepository->fetchCustomer($email);

        $this->assertNull($customerDto);
    }

    public function test_fetchCustomer_failure()
    {
        $this->expectException(ApiException::class);

        $email = 'john.doe@example.com';

        Http::fake([
            'https://api.paystack.co/customer/*' => Http::response([], 500),
        ]);

        $this->paystackRepository->fetchCustomer($email);
    }

    public function test_initializeTransaction_success()
    {
        $email = 'test@example.com';
        $amount = 5000;
        $isSubscription = true;

        $response = [
            'status' => true,
            'message' => 'Authorization URL created',
            'data' => [
                'authorization_url' => 'https://paystack.com/authorization-url',
                'access_code' => 'access_code',
                'reference' => 'reference',
            ],
        ];

        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response($response, 200),
        ]);

        $transactionInitializationDto = $this->paystackRepository->initializeTransaction($email, $amount, $isSubscription);

        $this->assertInstanceOf(TransactionInitializationDto::class, $transactionInitializationDto);
        $this->assertEquals($response['data']['authorization_url'], $transactionInitializationDto->getAuthorizationUrl());
        $this->assertEquals($response['data']['access_code'], $transactionInitializationDto->getAccessCode());
        $this->assertEquals($response['data']['reference'], $transactionInitializationDto->getReference());
    }

    public function test_initializeTransaction_failure()
    {
        $email = 'test@example.com';
        $amount = 5000;
        $isSubscription = false;

        $errorResponse = [
            'status' => false,
            'message' => 'Initialization failed',
        ];

        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response($errorResponse, 400),
        ]);

        Log::shouldReceive('critical')->once()->with('Fetch subscription error', Mockery::on(function ($data) {
            return isset($data['status'], $data['message'], $data['body']);
        }));

        $this->expectException(ServerErrorException::class);
        $this->expectExceptionMessage('Error Initializing Paystack Transaction');

        $this->paystackRepository->initializeTransaction($email, $amount, $isSubscription);
    }

    public function test_initializePurchaseTransaction_success()
    {
        $payload = [
            'email' => 'test@example.com',
            'amount' => 5000,
        ];

        $response = [
            'status' => true,
            'message' => 'Authorization URL created',
            'data' => [
                'authorization_url' => 'https://paystack.com/authorization-url',
                'access_code' => 'access_code',
                'reference' => 'reference',
            ],
        ];

        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response($response, 200),
        ]);

        $transactionInitializationDto = $this->paystackRepository->initializePurchaseTransaction($payload);

        $this->assertInstanceOf(TransactionInitializationDto::class, $transactionInitializationDto);
        $this->assertEquals($response['data']['authorization_url'], $transactionInitializationDto->getAuthorizationUrl());
        $this->assertEquals($response['data']['access_code'], $transactionInitializationDto->getAccessCode());
        $this->assertEquals($response['data']['reference'], $transactionInitializationDto->getReference());
    }

    public function test_initializePurchaseTransaction_failure()
    {
        $payload = [
            'email' => 'test@example.com',
            'amount' => 5000,
        ];

        $errorResponse = [
            'status' => false,
            'message' => 'Initialization failed',
        ];

        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response($errorResponse, 400),
        ]);

        Log::shouldReceive('critical')->once()->with('Fetch subscription error', Mockery::on(function ($data) {
            return isset($data['status'], $data['message'], $data['body']);
        }));

        $this->expectException(ServerErrorException::class);
        $this->expectExceptionMessage('Error Initializing Paystack Transaction For Purchase');

        $this->paystackRepository->initializePurchaseTransaction($payload);
    }

    public function test_createSubscription_success()
    {
        $customerId = 'cus_123456';

        $response = [
            'status' => true,
            'message' => 'Subscription created',
            'data' => [
                'id' => '12',
                'subscription_code' => 'sub_123456',
                'amount' => '5000',
                'customer' => $customerId,
                'plan' => 'plan_code_123456',
                'status' => 'active',
                'next_payment_date' => '2023-06-30T12:34:56Z',
                'createdAt' => '2023-06-30T12:34:56Z',
                'invoices' => [],
            ],
        ];

        Http::fake([
            'https://api.paystack.co/subscription' => Http::response($response, 200),
        ]);

        $subscriptionDto = $this->paystackRepository->createSubscription($customerId);

        $this->assertInstanceOf(SubscriptionDto::class, $subscriptionDto);
        $this->assertEquals($response['data']['subscription_code'], $subscriptionDto->getCode());
        $this->assertEquals($response['data']['createdAt'], $subscriptionDto->getCreatedAt());
    }

    public function test_createSubscription_failure()
    {
        $customerId = 'cus_123456';

        $errorResponse = [
            'status' => false,
            'message' => 'Subscription creation failed',
        ];

        Http::fake([
            'https://api.paystack.co/subscription' => Http::response($errorResponse, 400),
        ]);

        $this->expectException(ServerErrorException::class);
        $this->expectExceptionMessage('Error Subscribing User');

        $this->paystackRepository->createSubscription($customerId);
    }

    public function test_manageSubscription_success()
    {
        $subscriptionId = 'sub_123456';

        $response = [
            'status' => true,
            'message' => 'Manage link generated',
            'data' => [
                'link' => 'https://paystack.com/manage/subscription/123456',
            ],
        ];

        Http::fake([
            "https://api.paystack.co/subscription/{$subscriptionId}/manage/link" => Http::response($response, 200),
        ]);

        $manageLink = $this->paystackRepository->manageSubscription($subscriptionId);

        $this->assertEquals($response['data'], $manageLink);
    }

    public function test_manageSubscription_failure()
    {
        $subscriptionId = 'sub_123456';

        $errorResponse = [
            'status' => false,
            'message' => 'Subscription not found',
        ];

        Http::fake([
            "https://api.paystack.co/subscription/{$subscriptionId}/manage/link" => Http::response($errorResponse, 404),
        ]);

        Log::shouldReceive('critical')
            ->once()
            ->with('Manage Paystack error', Mockery::on(function ($context) {
                return isset($context['message']);
            }));

        $this->expectException(\Exception::class);

        $this->paystackRepository->manageSubscription($subscriptionId);
    }

    public function test_fetchSubscription_success()
    {
        $subscriptionId = 'sub_123456';
        $response = [
            'status' => true,
            'data' => [
                'id' => 123,
                'subscription_code' => 'sub_123456',
                'email_token' => 'email_token_123',
                'amount' => 5000,
                'interval' => 'monthly',
                'status' => 'active',
                'invoices' => [],
                'next_payment_date' => '12-03-2024',
            ],
        ];

        Http::fake([
            "https://api.paystack.co/subscription/{$subscriptionId}" => Http::response($response, 200),
        ]);

        $subscription = $this->paystackRepository->fetchSubscription($subscriptionId);

        $this->assertInstanceOf(SubscriptionDto::class, $subscription);
    }

    public function test_fetchSubscription_NotFound()
    {
        $subscriptionId = 'sub_123456';

        Http::fake([
            "https://api.paystack.co/subscription/{$subscriptionId}" => Http::response(null, 404),
        ]);

        Log::shouldReceive('critical')
            ->once()
            ->with('Fetch subscription error', Mockery::on(function ($context) {
                return $context['status'] === 404;
            }));

        $subscription = $this->paystackRepository->fetchSubscription($subscriptionId);

        $this->assertNull($subscription);
    }

    public function test_fetchSubscription_failure()
    {
        $subscriptionId = 'sub_123456';

        Http::fake([
            "https://api.paystack.co/subscription/{$subscriptionId}" => Http::response(['message' => 'Server error'], 500),
        ]);

        Log::shouldReceive('critical')
            ->once()
            ->with('Fetch subscription error', Mockery::on(function ($context) {
                return $context['status'] === 500;
            }));

        $subscription = $this->paystackRepository->fetchSubscription($subscriptionId);

        $this->assertNull($subscription);
    }

    public function test_getBankList_success()
    {
        $response = [
            'status' => true,
            'data' => [
                ['name' => 'Bank A', 'code' => '001', 'country' => 'Nigeria'],
                ['name' => 'Bank B', 'code' => '002', 'country' => 'Nigeria'],
            ],
        ];

        Http::fake([
            'https://api.paystack.co/bank?country=nigeria' => Http::response($response, 200),
        ]);

        $bankList = $this->paystackRepository->getBankList();

        $this->assertInstanceOf(Collection::class, $bankList);
        $this->assertCount(2, $bankList);
        $this->assertInstanceOf(BankDto::class, $bankList->first());
        $this->assertEquals('Bank A', $bankList->first()->getName());
        $this->assertEquals('001', $bankList->first()->getCode());
    }

    public function test_getBankList_failure()
    {
        Http::fake([
            'https://api.paystack.co/bank?country=nigeria' => Http::response(['message' => 'Server error'], 500),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Error Fetching Bank List from paystack', Mockery::on(function ($context) {
                return $context['code'] === 500;
            }));

        $bankList = $this->paystackRepository->getBankList();

        $this->assertNull($bankList);
    }

    public function test_validateAccountNumber_success()
    {
        $accountNumber = '1234567890';
        $bankCode = '001';

        $response = [
            'status' => true, // Simulating a successful response
        ];

        Http::fake([
            "https://api.paystack.co/bank/resolve?account_number={$accountNumber}&bank_code={$bankCode}" => Http::response($response, 200),
        ]);

        $isValid = $this->paystackRepository->validateAccountNumber($accountNumber, $bankCode);

        $this->assertTrue($isValid);
    }

    public function test_validateAccountNumber_failure()
    {
        $accountNumber = '1234567890';
        $bankCode = '001';

        $response = [
            'status' => false, // Simulating a failure response
        ];

        Http::fake([
            "https://api.paystack.co/bank/resolve?account_number={$accountNumber}&bank_code={$bankCode}" => Http::response($response, 200),
        ]);

        $isValid = $this->paystackRepository->validateAccountNumber($accountNumber, $bankCode);

        $this->assertFalse($isValid);
    }

    public function test_createTransferRecipient_success()
    {
        $name = 'John Doe';
        $accountNumber = '1234567890';
        $bankCode = '001';
        $recipient_code = 'RCP_m7ljkv8leesep7p';

        $response = [
            'data' => [
                'recipient_code' => $recipient_code,
                'name' => $name,
                'createdAt' => '2024-06-30T12:00:00Z',
            ],
        ];

        Http::fake([
            'https://api.paystack.co/transferrecipient' => Http::response($response, 200),
        ]);

        $recipientDto = $this->paystackRepository->createTransferRecipient($name, $accountNumber, $bankCode);

        $this->assertInstanceOf(TransferRecipientDto::class, $recipientDto);
        $this->assertEquals($name, $recipientDto->getName());
        $this->assertEquals($recipient_code, $recipientDto->getCode());
    }

    public function test_createTransferRecipient_failure()
    {
        $name = 'John Doe';
        $accountNumber = '1234567890';
        $bankCode = '001';

        $response = [
            'message' => 'Invalid bank code',
        ];

        Http::fake([
            'https://api.paystack.co/transferrecipient' => Http::response($response, 400),
        ]);

        $this->expectException(ServerErrorException::class);
        $this->expectExceptionMessage('Error Creating A Recipient');

        $this->paystackRepository->createTransferRecipient($name, $accountNumber, $bankCode);
    }

    public function test_initiateTransfer_success()
    {
        $amount = '5000';
        $recipientCode = 'RCP_m7ljkv8leesep7p';
        $transfer_code = 'TRF_1ptvuv321ahaa7q';

        $response = [
            'data' => [
                'amount' => $amount,
                'transfer_code' => $transfer_code,
                'createdAt' => '2024-06-30T12:00:00Z',
            ],
        ];

        Http::fake([
            'https://api.paystack.co/transfer' => Http::response($response, 200),
        ]);

        $transferDto = $this->paystackRepository->initiateTransfer($amount, $recipientCode, $recipientCode);

        $this->assertInstanceOf(TransferDto::class, $transferDto);
        $this->assertEquals($amount, $transferDto->getAmount());
        $this->assertEquals($transfer_code, $transferDto->getCode());
    }

    public function testInitiateTransferFailure()
    {
        $amount = '5000';
        $recipientCode = 'RC_xxxxxxx';
        $reference = 'REF_xxxxxxx';

        $response = [
            'message' => 'Insufficient funds',
        ];

        Http::fake([
            'https://api.paystack.co/transfer' => Http::response($response, 400),
        ]);

        $this->expectException(ServerErrorException::class);
        $this->expectExceptionMessage('Error Initiating Transfer');

        $this->paystackRepository->initiateTransfer($amount, $recipientCode, $reference);
    }
}
