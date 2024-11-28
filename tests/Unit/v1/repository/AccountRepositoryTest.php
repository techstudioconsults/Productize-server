<?php

namespace Tests\Unit\v1\repository;

use App\Exceptions\ModelCastException;
use App\Exceptions\ServerErrorException;
use App\Models\Account;
use App\Models\User;
use App\Repositories\AccountRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AccountRepository $accountRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an instance of the repository
        $this->accountRepository = new AccountRepository;
    }

    public function test_create(): void
    {
        $user = User::factory()->create(); // generating errors

        // Arrange
        $data = [
            'user_id' => $user->id,
            'name' => 'Test Account',
            'account_number' => '2079753003',
            'paystack_recipient_code' => '934fjcnwunu9231',
            'bank_code' => '552kcmi',
            'bank_name' => 'Banker',
        ];

        // Act
        $account = $this->accountRepository->create($data);

        // Assert
        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals($data['name'], $account->name);
    }

    public function test_create_invalid_data_throws_server_error_exception(): void
    {
        $this->expectException(ServerErrorException::class);

        $data = [
            'name' => 'Test Account',
            'account_number' => '2079753003',
            'paystack_recipient_code' => '934fjcnwunu9231',
            'bank_code' => '552kcmi',
            'bank_name' => 'Banker',
        ];

        $this->accountRepository->create($data);
    }

    public function test_query()
    {
        $expected_count = 10;

        $user = User::factory()->create();

        $accounts = Account::factory()->count($expected_count)->create([
            'user_id' => $user->id,
        ]);

        // Create another 10 accounts not belonging to test user
        Account::factory()->count(10)->create();

        // Arrange
        $filter = [
            'user_id' => $user->id,
        ];

        // Act
        $query = $this->accountRepository->query($filter);

        // Assert
        $this->assertInstanceOf(Builder::class, $query);
        $this->assertCount($expected_count, $query->get());
        $this->assertEquals($accounts->pluck('id')->sort()->values(), $query->pluck('id')->sort()->values());
        $this->assertEquals($user->id, $query->first()->user->id);
    }

    public function test_find()
    {
        $expected_count = 10;

        $user = User::factory()->create();

        $accounts = Account::factory()->count($expected_count)->create([
            'user_id' => $user->id,
        ]);

        // Create another 10 accounts not belonging to test user
        Account::factory()->count(10)->create();

        // Arrange
        $filter = [
            'user_id' => $user->id,
        ];

        // Act
        $accounts = $this->accountRepository->find($filter);

        // Assert
        $this->assertInstanceOf(Collection::class, $accounts);
        $this->assertCount($expected_count, $accounts);
        $this->assertEquals($accounts->pluck('id')->sort()->values(), $accounts->pluck('id')->sort()->values());
        $this->assertEquals($user->id, $accounts->first()->user->id);
    }

    public function test_findbyid()
    {
        // Arrange
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $id = $account->id;

        // Act
        $result = $this->accountRepository->findById($id);

        // Assert
        $this->assertInstanceOf(Account::class, $result);
        $this->assertEquals($id, $result->id);
    }

    public function test_find_by_id_not_found_return_null(): void
    {
        $result = $this->accountRepository->findById('12345');

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function test_findone()
    {
        // Arrange
        $user = User::factory()->create();

        $account = Account::factory()->create(['user_id' => $user->id]);

        $filter = [
            'name' => $account->name,
        ];

        // Act
        $result = $this->accountRepository->findOne($filter);

        // Assert
        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals($account->name, $result->name);
    }

    public function test_find_one_not_found_return_null(): void
    {
        $result = $this->accountRepository->findOne(['name' => '12345']);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function test_it_finds_active_account()
    {
        // Arrange
        $user = User::factory()->create();
        Account::factory()->count(2)->create(['user_id' => $user->id]);

        // Arrange

        // Act
        $result = $this->accountRepository->findActive(['user_id' => $user->id]);

        // Assert
        $this->assertInstanceOf(Account::class, $result);

        // Ensure only one active account per user
        $activeAccountsCount = $this->accountRepository->query(['user_id' => $user->id, 'active' => true])->count();
        $this->assertEquals(1, $activeAccountsCount);
    }

    /**
     * @test
     */
    public function test_it_updates_an_account()
    {
        // Arrange
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'active' => true]);
        $updates = [
            'name' => 'Updated Account',
        ];

        // Act
        $updatedAccount = $this->accountRepository->update($account, $updates);

        // Assert
        $this->assertInstanceOf(Account::class, $updatedAccount);
        $this->assertEquals('Updated Account', $updatedAccount->name);
    }

    /**
     * @test
     */
    public function test_it_throws_model_cast_exception()
    {
        // Arrange
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'active' => true]);
        $updates = [
            'name' => 'Updated Account',
        ];

        // Act and Assert
        $this->expectException(ModelCastException::class);
        $this->accountRepository->update($user, $updates);
    }
}
