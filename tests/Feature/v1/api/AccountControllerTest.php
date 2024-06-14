<?php

namespace Tests\Feature;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizedException;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\User;
use App\Repositories\PaystackRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_Index()
    {
        $user = User::factory()->create();

        $expected_count = 3;

        $accounts = Account::factory()->count($expected_count)->create([
            'user_id' => $user->id,
            'active' => 0
        ]);

        $expected_json = AccountResource::collection($accounts)->response()->getData(true);

        $this->actingAs($user);

        $response = $this->withoutExceptionHandling()->get(route('account.index'));

        $response->assertOk()->assertJson($expected_json, true)
            ->assertJsonCount($expected_count, 'data');
    }

    public function test_index_unauthenticated()
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()->get(route('account.index'));
    }

    public function test_index_unsubscribed_user()
    {
        $this->expectException(ForbiddenException::class);

        $user = User::factory()->create([
            'account_type' => 'free'
        ]);

        $this->actingAs($user);

        $this->withoutExceptionHandling()->get(route('account.index'));
    }

    public function test_store()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'account_number' => '0123456789',
            'bank_code' => '033',
            'name' => $this->faker->name,
            'bank_name' => 'Zenith Bank'
        ];

        // Mock PaystackRepository
        $paystackRepositoryMock = $this->partialMock(PaystackRepository::class);

        $paystackRepositoryMock->shouldReceive('validateAccountNumber')
            ->with($data['account_number'], $data['bank_code'])
            ->andReturn(true);

        $paystackRepositoryMock->shouldReceive('createTransferRecipient')
            ->with($data['name'], $data['account_number'], $data['bank_code'])
            ->andReturn(['recipient_code' => 'RCP_2x5j67tnnw1t98k']);


        $response = $this->post(route('account.store'), $data);
        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'name', 'account_number', 'bank_name']]);
    }

    public function test_StoreDuplicateAccount()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $accountNumber = '1234567898';
        $bankCode = '033';
        $name = $this->faker->name;
        $bankName = 'Zenith Bank';

        // Create an account with the same account number
        Account::factory()->create([
            'user_id' => $user->id,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
            'name' => $name,
            'bank_name' => $bankName,
        ]);

        $data = [
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
            'name' => $name,
            'bank_name' => $bankName,
        ];

        $response = $this->post(route('account.store'), $data);

        $response->assertStatus(409)
            ->assertJson(['message' => 'Duplicate Account']);
    }

    public function testStoreInvalidAccount()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'account_number' => '1234567890',
            'bank_code' => '033',
            'name' => $this->faker->name,
            'bank_name' => 'Zenith Bank'
        ];

        // Mock PaystackRepository
        $paystackRepositoryMock = $this->partialMock(PaystackRepository::class);

        // mock validation and return false
        $paystackRepositoryMock->shouldReceive('validateAccountNumber')
            ->with($data['account_number'], $data['bank_code'])
            ->andReturn(false);

        $response = $this->post(route('account.store'), $data);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid Account Number']);
    }

    public function test_Update()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $data = [
            'active' => false,
        ];

        $response = $this->patch(route('account.update', $account->id), $data);

        $response->assertStatus(200)
            ->assertJson(['data' => ['active' => false]]);
    }
}
