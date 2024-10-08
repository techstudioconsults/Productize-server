<?php

namespace Tests\Feature;

use App\Dtos\TransferDto;
use App\Enums\PayoutStatus;
use App\Exceptions\BadRequestException;
use App\Http\Resources\EarningResource;
use App\Models\Account;
use App\Models\Earning;
use App\Models\Payout;
use App\Models\User;
use App\Repositories\AccountRepository;
use App\Repositories\EarningRepository;
use App\Repositories\PayoutRepository;
use App\Repositories\PaystackRepository;
use App\Traits\SanctumAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EarningControllerTest extends TestCase
{
    use RefreshDatabase, SanctumAuthentication;

    protected $user;

    protected $earningRepository;

    protected $accountRepository;

    protected $paystackRepository;

    protected $payoutRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->actingAs($this->user);

        $this->earningRepository = Mockery::mock(EarningRepository::class);
        $this->accountRepository = Mockery::mock(AccountRepository::class);
        $this->paystackRepository = Mockery::mock(PaystackRepository::class);
        $this->payoutRepository = Mockery::mock(PayoutRepository::class);

        $this->app->instance(EarningRepository::class, $this->earningRepository);
        $this->app->instance(AccountRepository::class, $this->accountRepository);
        $this->app->instance(PaystackRepository::class, $this->paystackRepository);
        $this->app->instance(PayoutRepository::class, $this->payoutRepository);
    }

    public function test_index_earnings()
    {
        // $superAdmin = User::factory()->create(['role' => 'super_admin']);

        // Act as the super admin
        $this->actingAsSuperAdmin();

        // Create a mock query builder
        $earning = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);

        // Mock the EarningRepository methods
        $this->earningRepository->shouldReceive('query')->twice()->with([])->andReturn($earning);
        $earning->shouldReceive('sum')->with('total_earnings')->once()->andReturn(1000);
        $earning->shouldReceive('sum')->with('withdrawn_earnings')->once()->andReturn(300);

        // make the request
        $response = $this->getJson(route('earning.index'));

        // Assert the response

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total_earnings' => 1000,
                    'withdrawn_earnings' => 300,
                    'available_earnings' => 700,
                ],
            ]);
    }

    public function test_returns_user_earnings()
    {
        $earning = Earning::factory()->create(['user_id' => $this->user->id]);

        $this->earningRepository->shouldReceive('findOne')
            ->once()
            ->with(['user_id' => $this->user->id])
            ->andReturn($earning);

        $expected_json = EarningResource::make($earning)->response()->getData(true);

        $response = $this->withoutExceptionHandling()->get(route('earning.user'));

        $response->assertCreated()
            ->assertExactJson($expected_json, true);
    }

    public function test_withdraw_initiates_withdrawal_successfully()
    {
        $account = Account::factory()->create(['user_id' => $this->user->id, 'active' => true]);
        $earning = Earning::factory()->create(['user_id' => $this->user->id]);

        $this->accountRepository->shouldReceive('query')
            ->once()
            ->with(['user_id' => $this->user->id])
            ->andReturn(Account::query()->where('user_id', $this->user->id));

        $this->accountRepository->shouldReceive('findActive')
            ->once()
            ->andReturn($account);

        $this->earningRepository->shouldReceive('findOne')
            ->once()
            ->with(['user_id' => $this->user->id])
            ->andReturn($earning);

        $this->earningRepository->shouldReceive('getBalance')
            ->once()
            ->with($earning)
            ->andReturn(10000);

        $this->paystackRepository->shouldReceive('checkPTBalanceIsSufficient')
            ->once()
            ->with(5000)
            ->andReturn(true);

        $this->paystackRepository->shouldReceive('initiateTransfer')
            ->once()
            ->with(5000, $account->paystack_recipient_code, \Mockery::type('string'))
            ->andReturn(new TransferDto('5000', 'TRF_1ptvuv321ahaa7q', '12-03-2024'));

        $this->payoutRepository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($payout) use ($account) {
                return $payout['status'] === PayoutStatus::Pending->value &&
                    isset($payout['reference']) &&
                    $payout['paystack_transfer_code'] === 'TRF_1ptvuv321ahaa7q' &&
                    $payout['account_id'] === $account->id &&
                    $payout['amount'] === 5000;
            }));

        $this->earningRepository->shouldReceive('update')
            ->once()
            ->with($earning, ['pending' => 5000]);

        $response = $this->withoutExceptionHandling()->post(route('earning.withdraw'), ['amount' => 5000]);

        $response->assertStatus(200)
            ->assertJson(['data' => 'Withdrawal Initiated']);
    }

    public function test_withdraw_throws_error_if_no_payout_account()
    {
        $this->accountRepository->shouldReceive('query')
            ->once()
            ->with(['user_id' => $this->user->id])
            ->andReturn(Account::query()->where('user_id', $this->user->id));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('You need to set up your Payout account before requesting a payout.');

        $this->withoutExceptionHandling()->post(route('earning.withdraw'), ['amount' => 5000]);
    }

    public function test_withdraw_throws_error_if_insufficient_balance()
    {
        $earning = Earning::factory()->create(['user_id' => $this->user->id]);
        Payout::factory()->create([
            'account_id' => Account::factory()->create(['user_id' => $this->user->id]),
        ]);

        // Mock the account query
        $this->accountRepository->shouldReceive('query')
            ->once()
            ->with(['user_id' => $this->user->id])
            ->andReturn(Account::query()->where('user_id', $this->user->id));

        // Mock the findOne method for earnings
        $this->earningRepository->shouldReceive('findOne')
            ->once()
            ->with(['user_id' => $this->user->id])
            ->andReturn($earning);

        // Mock the getBalance method for earnings
        $this->earningRepository->shouldReceive('getBalance')
            ->once()
            ->with($earning)
            ->andReturn(1000);

        // Expect the exception due to insufficient balance
        $this->expectException(BadRequestException::class);

        // Perform the withdrawal action
        $this->withoutExceptionHandling()->post(route('earning.withdraw'), ['amount' => 5000]);

        // Ensure findActive is not called in this path
        $this->accountRepository->shouldReceive('findActive')
            ->never();
    }
}
