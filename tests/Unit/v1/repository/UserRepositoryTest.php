<?php

namespace Tests\Unit\v1\repository;

use App\Exceptions\BadRequestException;
use App\Exceptions\UnprocessableException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $userRepository;

    protected $full_name;
    protected $email;
    protected $password;

    public function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository();
        $this->full_name = "Tobi Olanitori";
        $this->email = "tobiolanitori@gmail.com";
        $this->password = "password123";
    }

    /**
     * Test Create User
     */
    public function test_create_user(): void
    {
        $credentials = [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'password' => $this->password
        ];

        // Create the user
        $user = $this->userRepository->createUser($credentials);

        // Assert user is saved in the db
        $this->assertDatabaseHas('users', [
            'email' => $this->email,
        ]);

        // Assert email is correctly saved
        $this->assertEquals($this->email, $user->email);

        // Assert the full name is correctly set
        $this->assertEquals($this->full_name, $user->full_name);

        // Assert an instance of User class is returned
        $this->assertInstanceOf(User::class, $user);

        // Assert the password is correctly set
        $this->assertTrue(Hash::check('password123', $user->password));

        // Assert the user gets a free trial
        $this->assertEquals("free_trial", $user->account_type);
    }

    public function test_create_user_with_no_email_throws_BadRequestException()
    {
        $this->expectException(BadRequestException::class);

        // Attempt to create a user without an email
        $this->userRepository->createUser([
            'full_name' => $this->full_name,
            'password' => $this->password
        ]);
    }

    public function test_update_user()
    {
        $credentials = [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'password' => $this->password
        ];

        // Create the user
        $user = $this->userRepository->createUser($credentials);

        $user = $this->userRepository->update('email', $this->email, [
            'full_name' => 'updated',
            'username' => 'updated',
            'phone_number' => '123456',
            'bio' => 'bio',
            'twitter_account' => 'https://account.com',
            'facebook_account' => 'https://account.com',
            'youtube_account' => 'https://account.com',
            'alt_email' => 'alt@email.com',
            'product_creation_notification' => true,
            'purchase_notification' => true,
            'news_and_update_notification' => true,
            'payout_notification' => true
        ]);

        // Assert email is correctly saved
        $this->assertEquals($this->email, $user->email);

        // Assert full name is correctly saved
        $this->assertEquals($user->full_name, 'updated');

        $this->assertEquals($user->username, 'updated');

        // Assert phone number is correctly saved
        $this->assertEquals($user->phone_number, '123456');

        // Assert bio is correctly saved
        $this->assertEquals($user->bio, 'bio');

        // Assert twitter is correctly saved
        $this->assertEquals($user->twitter_account, 'https://account.com');

        $this->assertEquals($user->facebook_account, 'https://account.com');

        $this->assertEquals($user->youtube_account, 'https://account.com');

        $this->assertEquals($user->alt_email, 'alt@email.com');

        $this->assertTrue((bool)$user->product_creation_notification);

        $this->assertTrue((bool)$user->news_and_update_notification);

        $this->assertTrue((bool)$user->payout_notification);
    }

    public function test_update_user_email_should_throw_bad_request_exception()
    {
        $this->expectException(BadRequestException::class);

        $credentials = [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'password' => $this->password
        ];

        // Create the user
        $this->userRepository->createUser($credentials);


        $this->userRepository->update('email', $this->email, [
            'email' => 'updated@email.com'
        ]);
    }

    public function test_update_user_with_invalid_filter_throws_UnprocessableException()
    {
        $this->expectException(UnprocessableException::class);

        $credentials = [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'password' => $this->password
        ];

        // Create the user
        $this->userRepository->createUser($credentials);


        $this->userRepository->update('invalid_column', "inavalid_column", [
            'full_name' => "this will not save",
        ]);
    }

    public function test_update_user_throws_model_not_found_exception() {
        $this->expectException(ModelNotFoundException::class);

        $this->userRepository->update('email', "unsavedemail@email.com", [
            'full_name' => "this will not save",
        ]);
    }

    // public function test_guarded_update() {

    // }
}
