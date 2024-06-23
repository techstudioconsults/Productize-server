<?php

namespace Tests\Feature\v1\api;

use App\Enums\RevenueActivity;
use App\Exceptions\ForbiddenException;
use App\Http\Resources\RevenueResource;
use App\Models\Revenue;
use Database\Seeders\RevenueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Traits\SanctumAuthentication;

class RevenueControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker, SanctumAuthentication;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function super_admin_can_list_revenues()
    {
        $this->actingAsSuperAdmin();

        // Ensure to keep pagination count, else test fails
        $revenues = Revenue::factory()->count(10)->create([
            'created_at' => now()->subDay()->toDateString(),
        ]);

        $expected_json = RevenueResource::collection($revenues)->response()->getData(true);

        $response = $this->withoutExceptionHandling()->get(route('revenue.index', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));


        $response->assertOk()->assertJson($expected_json, true);
        $response->assertJsonStructure(['data', 'links', 'meta']);
        $this->assertCount(10, $response->json('data')); // Default pagination count
    }

    /** @test */
    public function non_super_admin_cannot_list_revenues()
    {
        $this->expectException(ForbiddenException::class);

        $this->actingAsAdmin();

        Revenue::factory()->count(15)->create();

        $this->withoutExceptionHandling()->get(route('revenue.index', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));
    }

    /** @test */
    public function super_admin_can_retrieve_revenue_statistics()
    {
        $this->actingAsSuperAdmin();

        Revenue::factory()->create([
            'activity' => RevenueActivity::PURCHASE->value,
            'product' => 'Subscription',
            'amount' => 100,
            'commission' => 5.00,
        ]);

        $response = $this->withoutExceptionHandling()->get(route('revenue.stats'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'total_revenues',
                'total_sale_revenue',
                'total_subscription_revenue',
                'total_commission'
            ]
        ]);

        $this->assertEquals(100, $response->json('data.total_revenues'));
        $this->assertEquals(100, $response->json('data.total_sale_revenue'));
        $this->assertEquals(100, $response->json('data.total_subscription_revenue'));
        $this->assertEquals(5, $response->json('data.total_commission'));
    }

    /** @test */
    public function non_super_admin_cannot_retrieve_revenue_statistics()
    {
        $this->expectException(ForbiddenException::class);

        $this->actingAsAdmin();

        Revenue::factory()->create([
            'activity' => RevenueActivity::PURCHASE->value,
            'product' => 'Subscription',
            'amount' => 100,
            'commission' => 5.00,
        ]);

        $this->withoutExceptionHandling()->get(route('revenue.stats'));
    }

    /** @test */
    public function super_admin_can_download_revenues_as_csv()
    {
        $this->actingAsSuperAdmin();

        $this->seed(RevenueSeeder::class); // Seed the database with test data

        $response = $this->withoutExceptionHandling()->get(route('revenue.download', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=revenues_' . now()->isoFormat('DD_MMMM_YYYY') . '.csv');
    }

    /** @test */
    public function non_super_admin_cannot_download_revenues_as_csv()
    {
        $this->expectException(ForbiddenException::class);

        $this->actingAsAdmin();

        Revenue::factory()->count(10)->create();

        $this->withoutExceptionHandling()->get(route('revenue.download', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));
    }

    /** @test */
    public function super_admin_handles_no_revenues_for_given_filter_in_download()
    {
        $this->actingAsSuperAdmin();

        // Seed the database with test data
        $this->seed(RevenueSeeder::class);

        // Specify a date range that doesn't overlap with seeded data
        $response = $this->get(route('revenue.download', [
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-31',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertEquals('', $response->getContent()); // Assert that response content is empty
    }


    /** @test */
    public function non_super_admin_handles_no_revenues_for_given_filter_in_download()
    {
        $this->expectException(ForbiddenException::class);

        $this->actingAsAdmin();

        $this->withoutExceptionHandling()->get(route('revenue.download', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));
    }
}
