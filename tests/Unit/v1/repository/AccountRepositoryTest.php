<?php

namespace Tests\Unit\v1\repository;

use App\Models\Account;
use App\Models\User;
use App\Repositories\AccountRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
}
