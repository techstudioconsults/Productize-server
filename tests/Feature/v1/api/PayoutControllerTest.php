<?php

namespace Tests\Feature\v1\api;

use App\Exceptions\UnAuthorizedException;
use App\Http\Resources\PayoutResource;
use App\Models\Account;
use App\Models\Payout;
use App\Models\User;
use App\Traits\SanctumAuthentication;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Storage;
use Tests\TestCase;

class PayoutControllerTest extends TestCase
{
    use RefreshDatabase, SanctumAuthentication;

    public function test_super_admin_can_view_payouts_with_date_filters()
    {
        $this->actingAsSuperAdmin();

        $expected_count = 1;

        // Create payouts for testing
        Payout::factory()->count($expected_count)->create(['created_at' => now()->subDays(10)]);

        Payout::factory()->create(['amount' => 200, 'created_at' => now()->subDays(5)]);

        // Call the index endpoint with date filters
        $response = $this->get(route('payout.index', [
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response
            ->assertJson(
                fn (AssertableJson $json) => $json->has('meta')
                    ->has('links')
                    ->has('data', $expected_count)
            );
    }

    public function test_super_admin_can_view_all_payouts_without_filters()
    {
        $this->actingAsSuperAdmin();

        $expected_count = 5; // set to 5 because pagination is 5
        $user = User::factory()->create();

        $payouts = Payout::factory()->count($expected_count)->create([
            'account_id' => Account::factory()->create(['user_id' => $user->id]),
        ]);

        // Call the index endpoint without filters
        $response = $this->get(route('payout.index'));

        // Convert the payouts to PayoutResource
        $expected_json = PayoutResource::collection($payouts)->response()->getData(true);
        $response->assertOk()->assertJson($expected_json, true);
        $response
            ->assertJson(
                fn (AssertableJson $json) => $json->has('meta')
                    ->has('links')
                    ->has('data', $expected_count)
            );
    }

    public function test_non_super_admin_cannot_view_payouts()
    {
        $this->actingAsRegularUser();

        // Call the index endpoint
        $response = $this->get(route('payout.index'));

        // Assert forbidden response
        $response->assertForbidden();
    }

    /**
     * Test user works correctly.
     */
    public function test_user(): void
    {
        $expected_count = 5; // set to 5 because pagination is 5
        $user = User::factory()->create();

        $payouts = Payout::factory()->count($expected_count)->create([
            'account_id' => Account::factory()->create(['user_id' => $user->id]),
        ]);

        $response = $this->actingAs($user, 'web')->get(route('payout.user'));

        // Convert the payouts to PayoutResource
        $expected_json = PayoutResource::collection($payouts)->response()->getData(true);
        $response->assertOk()->assertJson($expected_json, true);
        $response
            ->assertJson(
                fn (AssertableJson $json) => $json->has('meta')
                    ->has('links')
                    ->has('data', $expected_count)
            );
    }

    public function test_user_unauthenticated()
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()->get(route('payout.user'));
    }

    public function test_download()
    {
        // Create a mock user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user);

        // Mock the start_date and end_date for the request
        $start_date = Carbon::now()->subDays(7)->format('Y-m-d');
        $end_date = Carbon::now()->format('Y-m-d');

        // Mock the payouts data
        Payout::factory()->count(5)->create([
            'account_id' => Account::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $requestParams = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        // Make the request to download the CSV file
        $response = $this->get(route('payout.download'), $requestParams);

        // Assert response is successful and CSV headers are correct
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=payouts_'.Carbon::today()->isoFormat('DD_MMMM_YYYY').'.csv');

        // Clean up: Delete the CSV file from storage after testing
        Storage::disk('local')->delete('csv/payouts_'.Carbon::today()->isoFormat('DD_MMMM_YYYY').'.csv');
    }

    public function test_download_unauthenticated()
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()->get(route('payout.download'));
    }

    public function test_download_unauthenticated_for_superAdmin()
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()->get(route('payout.downloadPayout'));
    }

    public function test_download_for_superAdmin()
    {
        // Create a mock user and authenticate
        $user = User::where('email', 'tobi.olanitori.binaryartinc@gmail.com')->firstOr(function () {
            return User::factory()->create([
                'email' => 'tobi.olanitori.binaryartinc@gmail.com',
                'full_name' => 'Tobi Olanitori',
            ]);
        });

        $this->actingAs($user);

        // Mock the start_date and end_date for the request
        $start_date = Carbon::now()->subDays(7)->format('Y-m-d');
        $end_date = Carbon::now()->format('Y-m-d');

        // Mock the payouts data
        Payout::factory()->count(5)->create([
            'account_id' => Account::factory()->create(['user_id' => $user->id])->id,
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $requestParams = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        // Make the request to download the CSV file
        $response = $this->get(route('payout.downloadPayout'), $requestParams);

        // Assert response is successful and CSV headers are correct
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=payouts_'.Carbon::today()->isoFormat('DD_MMMM_YYYY').'.csv');

        // Clean up: Delete the CSV file from storage after testing
        Storage::disk('local')->delete('csv/payouts_'.Carbon::today()->isoFormat('DD_MMMM_YYYY').'.csv');
    }
}
