<?php

namespace Tests\Unit\Repositories;

use App\Exceptions\ModelCastException;
use App\Models\Payout;
use App\Models\User;
use App\Repositories\PayoutRepository;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PayoutRepositoryTest extends TestCase
{
    protected PayoutRepository $payoutRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payoutRepository = new PayoutRepository();
    }

    public function test_Create()
    {
        $credentials = [
            'account_id' => 1,
            'reference' => 'REF123456',
            'status' => 'completed',
            'paystack_transfer_code' => 'TRANSFER123',
            'amount' => 5000
        ];

        $payout = $this->payoutRepository->create($credentials);

        $this->assertInstanceOf(Payout::class, $payout);
        $this->assertEquals('REF123456', $payout->reference);
    }

    public function test_Query()
    {
        $filter = [
            'status' => 'completed',
        ];

        $query = $this->payoutRepository->query($filter);

        $this->assertInstanceOf(Builder::class, $query);
    }

    public function test_Find()
    {
        Payout::factory()->count(3)->create();
        $filter = [
            'status' => 'completed',
        ];

        $result = $this->payoutRepository->find($filter);

        $this->assertInstanceOf(Collection::class, $result);

    }

    public function test_Find_By_Id()
    {
        $payout = Payout::factory()->create();
        $result = $this->payoutRepository->findById($payout->id);

        $this->assertInstanceOf(Payout::class, $result);
        $this->assertEquals($payout->id, $result->id);
    }

    public function test_Find_One()
    {
        $payout = Payout::factory()->create();
        $filter = [
            'reference' => $payout->reference,
        ];

        $result = $this->payoutRepository->findOne($filter);

        $this->assertInstanceOf(Payout::class, $result);
        $this->assertEquals($payout->id, $result->id);
    }

    public function test_Update()
    {
        $payout = Payout::factory()->create();
        $updates = [
            'status' => 'failed',
        ];

        $result = $this->payoutRepository->update($payout, $updates);

        $this->assertInstanceOf(Payout::class, $result);
        $this->assertEquals('failed', $result->status);
    }

    public function test_Update_Throws_ModelCastException()
    {
        $this->expectException(ModelCastException::class);

        $user = User::factory()->create();
        $updates = [
            'status' => 'failed',
        ];

        $this->payoutRepository->update($user, $updates);
    }
}
