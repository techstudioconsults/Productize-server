<?php

namespace Tests\Feature;

use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnprocessableException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * It should create a new user
     */
    public function test_create_repository(): void
    {
        Event::fake();

        $userRepository = new UserRepository();

        $credentials = [
            'email' => 'tobiolanitori@gmail.com',
            'full_name' => 'Tobi Olanitori',
            'password' => 'password123'
        ];

        $user = $userRepository->createUser($credentials);

        $this->assertModelExists($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Tobi Olanitori', $user->full_name);
        $this->assertEquals('tobiolanitori@gmail.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        Event::assertDispatched(Registered::class);
    }

    public function test_create_with_no_email_repository()
    {
        $this->expectException(BadRequestException::class);

        $userRepository = new UserRepository();

        $userRepository->createUser([
            'full_name' => 'Tobi Olanitori',
            'password' => 'password123'
        ]);
    }

    public function test_update_repository()
    {
        $userRepository = new UserRepository();

        $user = User::factory()->create();

        $updatedUser = $userRepository->update('email', $user->email, ['full_name' => 'Tobi Olanitori']);

        $this->assertModelExists($updatedUser);
        $this->assertEquals('Tobi Olanitori', $updatedUser->full_name);
        $this->assertInstanceOf(User::class, $updatedUser);
    }

    public function test_update_repository_no_filter_in_schema()
    {
        $userRepository = new UserRepository();

        // It should throw error if filter column is not in the schema.
        $this->expectException(UnprocessableException::class);

        $user = User::factory()->create();

        $userRepository->update('emails', $user->email, ['full_name' => 'Tobi Olanitori']);
    }

    public function test_guarded_update_repository()
    {
        $userRepository = new UserRepository();

        $user = User::factory()->create();

        $updatedUser = $userRepository->guardedUpdate($user->email, 'password', 'password123');

        $this->assertTrue(Hash::check('password123', $updatedUser->password));
        $this->assertEquals($updatedUser->email, $user->email);
    }

    public function test_guarded_update_user_not_found()
    {
        $userRepository = new UserRepository();

        // Mock the critical log method so it doesn't actually log during the test.
        Log::shouldReceive('critical')->andReturnNull();

        // It should throw error if filter column is not in the schema.
        $this->expectException(NotFoundException::class);

        $userRepository->guardedUpdate('tobiolanitori@gmail.com', 'password', 'password123');
    }
}
