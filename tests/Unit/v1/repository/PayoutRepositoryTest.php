<?php

namespace Tests\Unit\Repositories;

use App\Enums\PayoutStatus;
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
        $this->payoutRepository = new PayoutRepository;
    }

    public function test_create()
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

    public function test_query()
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
            'status' => PayoutStatus::Pending->value,
        ]);

        $filter = [
            'status' => PayoutStatus::Completed->value,
        ];

        $query = $this->payoutRepository->query($filter);

        $this->assertInstanceOf(Builder::class, $query);

        $this->assertCount($expected_count, $query->get());

        $this->assertEquals($payouts->pluck('id')->sort()->values(), $query->pluck('id')->sort()->values());
    }

    public function test_find()
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
            'status' => PayoutStatus::Pending->value,
        ]);

        $filter = [
            'status' => PayoutStatus::Completed->value,
        ];

        $result = $this->payoutRepository->find($filter);

        $this->assertInstanceOf(Collection::class, $result);

        $this->assertCount($expected_count, $result);

        $this->assertEquals($payouts->pluck('id')->sort()->values(), $result->pluck('id')->sort()->values());
    }

    public function test_find_by_id()
    {
        $payout = Payout::factory()->create([
            'account_id' => Account::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
            'status' => PayoutStatus::Pending->value,
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

    public function test_find_one()
    {
        $payout = Payout::factory()->create([
            'account_id' => Account::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
            'status' => PayoutStatus::Pending->value,
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

    public function test_update()
    {
        $payout = Payout::factory()->create([
            'account_id' => Account::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
            'status' => PayoutStatus::Pending->value,
        ]);

        $updates = [
            'status' => PayoutStatus::Failed->value,
        ];

        $result = $this->payoutRepository->update($payout, $updates);

        $this->assertInstanceOf(Payout::class, $result);

        $this->assertEquals(PayoutStatus::Failed->value, $result->status);
    }

    public function test_update_throws_model_cast_exception()
    {
        $this->expectException(ModelCastException::class);

        $user = User::factory()->create();
        $updates = [
            'status' => 'failed',
        ];

        $this->payoutRepository->update($user, $updates);
    }
}
