<?php

namespace Tests\Unit\Repositories;

use App\Enums\RevenueActivity;
use App\Models\Revenue;
use App\Repositories\RevenueRepository;
use Database\Seeders\RevenueSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RevenueRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    protected RevenueRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new RevenueRepository;
    }

    /** @test */
    public function it_can_create_a_revenue()
    {
        $data = [
            'user_id' => 1,
            'activity' => 'purchase',
            'product' => 'Subscription',
            'amount' => 100,
            'commission' => 5.00,
        ];

        $revenue = $this->repository->create($data);

        $this->assertInstanceOf(Revenue::class, $revenue);
        $this->assertEquals($data['user_id'], $revenue->user_id);
        $this->assertEquals($data['activity'], $revenue->activity);
        $this->assertEquals($data['product'], $revenue->product);
        $this->assertEquals($data['amount'], $revenue->amount);
        $this->assertEquals($data['commission'], $revenue->commission);
    }

    /** @test */
    public function it_can_query_revenues()
    {
        $expected_count = 10;

        $this->seed(RevenueSeeder::class);

        // Query all revenues
        $revenues = $this->repository->query([]);

        $this->assertCount($expected_count, $revenues->get());
    }

    /** @test */
    public function it_can_find_revenues()
    {
        $expected_count = 10;

        $this->seed(RevenueSeeder::class);

        // Find revenues using the repository
        $foundRevenues = $this->repository->find([]);

        $this->assertCount($expected_count, $foundRevenues);
    }

    /** @test */
    public function it_can_find_a_revenue_by_id()
    {
        // Create a revenue
        $revenue = Revenue::factory()->create();

        // Find revenue by ID using the repository
        $foundRevenue = $this->repository->findById($revenue->id);

        $this->assertInstanceOf(Revenue::class, $foundRevenue);
        $this->assertEquals($revenue->id, $foundRevenue->id);
    }

    /** @test */
    public function it_can_find_one_revenue_by_filter()
    {
        // Create some revenues
        Revenue::factory()->count(5)->create(['activity' => 'purchase']);

        // Find one revenue by filter using the repository
        $filter = ['activity' => RevenueActivity::PURCHASE->value];

        $foundRevenue = $this->repository->findOne($filter);

        $this->assertInstanceOf(Revenue::class, $foundRevenue);
        $this->assertEquals(RevenueActivity::PURCHASE->value, $foundRevenue->activity);
    }

    /** @test */
    public function it_can_update_a_revenue()
    {
        // Create a revenue
        $revenue = Revenue::factory()->create();

        // Update the revenue using the repository
        $updatedData = ['amount' => 200];
        $updatedRevenue = $this->repository->update($revenue, $updatedData);

        $this->assertEquals($updatedData['amount'], $updatedRevenue->amount);
        $this->assertEquals($revenue->id, $updatedRevenue->id);
    }

    /** @test */
    public function it_returns_null_when_finding_by_id_for_non_existing_revenue()
    {
        // Attempt to find a non-existing revenue by ID using the repository
        $foundRevenue = $this->repository->findById('non-existing-id');

        $this->assertNull($foundRevenue);
    }

    /** @test */
    public function it_returns_null_when_finding_one_for_non_existing_filter()
    {
        // Attempt to find a non-existing revenue by filter using the repository
        $filter = ['activity' => 'non-existing-activity'];
        $foundRevenue = $this->repository->findOne($filter);

        $this->assertNull($foundRevenue);
    }

    /** @test */
    public function it_can_handle_empty_filter_when_finding_revenues()
    {
        // Create some revenues
        Revenue::factory()->count(5)->create();

        // Find revenues with an empty filter using the repository
        $foundRevenues = $this->repository->find(null);

        $this->assertCount(5, $foundRevenues);
    }
}
