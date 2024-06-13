<?php

namespace Tests\Unit\v1\repository;

use App\Repositories\EarningRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Exceptions\ModelCastException;
use App\Models\Earning;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EarningRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EarningRepository $earningRepository;


    public function setUp(): void
    {
        parent::setUp();


        $this->earningRepository = new EarningRepository();
    }


    /**
     * TEST
     */
    public function test_create_earning(): void
    {
        $user = User::factory()->create();
        $entity = [
            'user_id' => $user->id,
            'amount' => 1000
        ];

        // Act
        $earning = $this->earningRepository->create($entity);

        // Assert
        $this->assertInstanceOf(Earning::class, $earning);
        $this->assertEquals(1000, $earning->total_earnings);
    }

    /**
     * TEST
     */
    public function test_query_earning()
    {
        $user = User::factory()->create();

        $filter = [
            'user_id' => $user->id
        ];

        $query = $this->earningRepository->query($filter);

        $this->assertInstanceOf(Builder::class, $query);
    }

    public function test_find_earning()
    {
        $user = User::factory()->create();

        $filter = [
            'user_id' => $user->id
        ];

        $result = $this->earningRepository->find($filter);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_find_by_id()
    {
        $user = User::factory()->create();
        $earning = Earning::factory()->create([
            'user_id' => $user->id
        ]);

        $result = $this->earningRepository->findById($earning->id);

        $this->assertInstanceOf(Earning::class, $result);
    }

    public function test_find_one()
    {
        $user = User::factory()->create();
        $earning = Earning::factory()->create([
            'user_id' => $user->id
        ]);
        $filter = [
            'user_id' => $earning->user_id,
        ];

        $result = $this->earningRepository->findOne($filter);

        $this->assertInstanceOf(Earning::class, $result);
    }

    public function test_update_earning()
    {
        $user = User::factory()->create();
        $earning = Earning::factory()->create([
            'user_id' => $user->id
        ]);
        $updates = [
            'total_earnings' => 2000,
        ];

        $result = $this->earningRepository->update($earning, $updates);

        $this->assertInstanceOf(Earning::class, $result);
        $this->assertEquals(2000, $result->total_earnings);
    }

    public function test_update_to_throw_modelCastException()
    {

        $user = User::factory()->create();
        $earning = Earning::factory()->create([
            'user_id' => $user->id
        ]);
        $updates = [
            'total_earnings' => 2000,
        ];

        // model casting exception
        $this->expectException(ModelCastException::class);
        $this->earningRepository->update($user, $updates);
    }

    public function test_get_earning_balance()
    {
        $user = User::factory()->create();
        $earning = Earning::factory()->create([
            'user_id' => $user->id,
            'total_earnings' => 3000,
            'withdrawn_earnings' => 1000
        ]);

        $balance = $this->earningRepository->getBalance($earning);

        $this->assertEquals(2000, $balance);
    }
}
