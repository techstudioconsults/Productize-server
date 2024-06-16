<?php

namespace Tests\Unit\Repositories;

use App\Enums\PayoutStatusEnum;
use App\Exceptions\ModelCastException;
use App\Models\Account;
use App\Models\Payout;
use App\Models\User;
use App\Repositories\PayoutRepository;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected PayoutRepository $payoutRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payoutRepository = new PayoutRepository();
    }

    public function test_Create()
    {
        $credentials = [
            'account_id' => Account::factory()->create(['user_id' => User::factory()->create()->id])->id,
            'reference' => 'REF123456',
            'status' => 'completed',
            'paystack_transfer_code' => 'TRANSFER123',
            'amount' => 5000,
        ];

        $payout = $this->payoutRepository->create($credentials);

        $this->assertInstanceOf(Payout::class, $payout);
        $this->assertEquals('REF123456', $payout->reference);
    }

    public function test_Query()
    {
        $expected_count = 10;

        $payouts = Payout::factory()->count($expected_count)->create([
            'account_id' => Account::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
        ]);

        // Create Payout with pending status
        Payout::factory()->create([
            'account_id' => Account::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
            'status' => PayoutStatusEnum::Pending->value,
        ]);

        $filter = [
            'status' => PayoutStatusEnum::Completed->value,
        ];

        $query = $this->payoutRepository->query($filter);

        $this->assertInstanceOf(Builder::class, $query);

        $this->assertCount($expected_count, $query->get());

        $this->assertEquals($payouts->pluck('id')->sort()->values(), $query->pluck('id')->sort()->values());
    }

    public function test_Find()
    {
        $expected_count = 10;

        $payouts = Payout::factory()->count($expected_count)->create([
            'account_id' => Account::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
        ]);

        // Create Payout with pending status
        Payout::factory()->create([
            'account_id' => Account::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
            'status' => PayoutStatusEnum::Pending->value,
        ]);

        $filter = [
            'status' => PayoutStatusEnum::Completed->value,
        ];

        $result = $this->payoutRepository->find($filter);

        $this->assertInstanceOf(Collection::class, $result);

        $this->assertCount($expected_count, $result);

        $this->assertEquals($payouts->pluck('id')->sort()->values(), $result->pluck('id')->sort()->values());
    }

    public function test_Find_By_Id()
    {
        $payout = Payout::factory()->create([
            'account_id' => Account::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
            'status' => PayoutStatusEnum::Pending->value,
        ]);

        $result = $this->payoutRepository->findById($payout->id);

        $this->assertInstanceOf(Payout::class, $result);
        $this->assertEquals($payout->id, $result->id);
        $this->assertEquals($payout->toArray(), $result->toArray());
    }

    /**
     * test_find_by_id_not_found_return_null
     */
    public function test_find_by_id_not_found_return_null(): void
    {
        $result = $this->payoutRepository->findById('12345');

        $this->assertNull($result);
    }

    public function test_Find_One()
    {
        $payout = Payout::factory()->create([
            'account_id' => Account::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
            'status' => PayoutStatusEnum::Pending->value,
        ]);

        $filter = [
            'reference' => $payout->reference,
        ];

        $result = $this->payoutRepository->findOne($filter);

        $this->assertInstanceOf(Payout::class, $result);
        $this->assertEquals($payout->id, $result->id);
        $this->assertEquals($payout->toArray(), $result->toArray());
    }

    public function test_find_one_not_found_return_null(): void
    {
        $result = $this->payoutRepository->findOne(['reference' => '12345']);

        $this->assertNull($result);
    }

    public function test_Update()
    {
        $payout = Payout::factory()->create([
            'account_id' => Account::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
            'status' => PayoutStatusEnum::Pending->value,
        ]);

        $updates = [
            'status' => PayoutStatusEnum::Failed->value,
        ];

        $result = $this->payoutRepository->update($payout, $updates);

        $this->assertInstanceOf(Payout::class, $result);

        $this->assertEquals(PayoutStatusEnum::Failed->value, $result->status);
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
