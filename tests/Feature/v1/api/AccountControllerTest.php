<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
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
        $account = Account::factory()->count(3)->create([
            'user_id' => $user->id
        ]);
        $this->actingAs($user);

        $response = $this->withoutExceptionHandling()->get(route('account.index'));
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
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
        $paystackRepository = Mockery::mock('App\Repositories\PaystackRepository');
        $paystackRepository->shouldReceive('validateAccountNumber')
            ->with($data['account_number'], $data['bank_code'])
            ->andReturn(true);

        $paystackRepository->shouldReceive('createTransferRecipient')
            ->with($data['name'], $data['account_number'], $data['bank_code'])
            ->andReturn(['recipient_code' => 'RCP_123456']);

        $this->app->instance('App\Repositories\PaystackRepository', $paystackRepository);


        $response = $this->post(route('account.store'), $data);
        $response->assertStatus(201)
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
        $paystackRepository = Mockery::mock('App\Repositories\PaystackRepository');
        $paystackRepository->shouldReceive('validateAccountNumber')
            ->with($data['account_number'], $data['bank_code'])
            ->andReturn(false);

        $this->app->instance('App\Repositories\PaystackRepository', $paystackRepository);

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

    // public function test_BankList()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user);

    //     $response = $this->get(route('account.bank-list'));

    //     $response->assertStatus(200)
    //         ->assertJsonStructure([['name', 'code']]);
    // }
}
