<?php

namespace Tests\Unit\v1\repository;

use App\Models\Account;
use App\Models\User;
use App\Repositories\AccountRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use App\Exceptions\ModelCastException;


class AccountRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AccountRepository $accountRepository;

    public function setUp(): void
    {
        parent::setUp();

        // Create an instance of the repository
        $this->accountRepository = new AccountRepository();
    }

    /**
     * A basic unit test example.
     */
    public function test_create(): void
    {
        $user = User::factory()->create(); // generating errors

        // Arrange
        $data = [
            'user_id' => $user->id,
            'name' => 'Test Account',
            'account_number' => "134rfcikc892noc9",
            'paystack_recipient_code' => '934fjcnwunu9231',
            'bank_code' => "552kcmi",
            'bank_name' => "Banker",
            'active' => true
        ];

        // Act
        $account = $this->accountRepository->create($data);

        // Assert
        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals('Test Account', $account->name);
    }

    /**
     * @test
     */
    public function test_it_queries_accounts()
    {
        // Arrange
        $filter = [
            'name' => 'Test Account',
        ];

        // Act
        $query = $this->accountRepository->query($filter);

        // Assert
        $this->assertInstanceOf(Builder::class, $query);
    }

    /**
     * @test
     */
    public function test_it_finds_accounts()
    {
        // Arrange
        $filter = [
            'name' => 'Test Account',
        ];

        // Act
        $accounts = $this->accountRepository->find($filter);

        // Assert
        $this->assertInstanceOf(Collection::class, $accounts);
    }

    /**
     * @test
     */
    public function test_it_finds_an_account_by_id()
    {
        // Arrange
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $id = $account->id;

        // Act
        $account = $this->accountRepository->findById($id);

        // Assert
        $this->assertInstanceOf(Account::class, $account);
    }

    /**
     * @test
     */
    public function test_it_finds_an_account_by_filter()
    {


        // Arrange
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $filter = [
            'name' => $account->name,
        ];

        // Act
        $account = $this->accountRepository->findOne($filter);

        // Assert
        $this->assertInstanceOf(Account::class, $account);
    }

    /**
     * @test
     */
    public function test_it_finds_active_account()
    {
        // Arrange
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'active' => true]);
        // Arrange

        // Act
        $account = $this->accountRepository->findActive();

        // Assert
        $this->assertInstanceOf(Account::class, $account);
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
