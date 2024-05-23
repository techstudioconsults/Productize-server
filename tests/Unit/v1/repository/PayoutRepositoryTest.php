<?php

namespace Tests\Unit\v1\repository;

use App\Repositories\PayoutRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class PayoutRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PayoutRepository $payoutRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->payoutRepository = app(PayoutRepository::class);
    }

    public function test_create()
    {
        // Arrange
        $payoutData = [
            'pay_out_account_id' => 1,
            'reference' => 'Payout-123',
            'status' => 'pending',
            'paystack_transfer_code' => 'TR-123456',
            'amount' => 100.00,
        ];

        // Act
        $payout = $this->payoutRepository->create($payoutData);

        // Assert
        $this->assertInstanceOf(PayoutRepository::class, $payout);
        $this->assertEquals($payoutData['pay_out_account_id'], $payout->pay_out_account_id);
        $this->assertEquals($payoutData['reference'], $payout->reference);
        $this->assertEquals($payoutData['status'], $payout->status);
        $this->assertEquals($payoutData['paystack_transfer_code'], $payout->paystack_transfer_code);
        $this->assertEquals($payoutData['amount'], $payout->amount);
    }
}
