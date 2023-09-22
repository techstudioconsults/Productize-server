<?php

namespace Tests\Feature;

use App\Exceptions\BadRequestException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
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
}
