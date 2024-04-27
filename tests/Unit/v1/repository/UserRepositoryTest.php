<?php

namespace Tests\Unit\v1\repository;

use App\Exceptions\BadRequestException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $full_name;
    protected $email;
    protected $password;

    public function setUp(): void
    {
        parent::setUp();
    }
    /**
     * Test Create User
     */
    public function test_create_user(): void
    {
        $full_name = "Tobi Olanitori";
        $email = "tobiolanitori@gmail.com";
        $password = "password123";

        $credentials = ['full_name' => $full_name, 'email' => $email, 'password' => $password];

        $userRepository = new UserRepository();

        $user = $userRepository->createUser($credentials);

        $this->assertEquals($email, $user->email);
        $this->assertEquals($full_name, $user->full_name);
        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);
    }

    public function test_create_user_with_no_email_throws_BadRequestException()
    {
        $this->expectException(BadRequestException::class);

        $userRepository = new UserRepository();

        $userRepository->createUser([
            'full_name' => 'Tobi Olanitori',
            'password' => 'password123'
        ]);
    }
}
