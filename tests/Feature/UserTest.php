<?php

namespace Tests\Feature;

use App\Exceptions\BadRequestException;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    // create a user
    // try to create a user without email and throw a BAD exception
    // mock register event
    //


    use RefreshDatabase;

    /**
     * It should create a new user
     */
    public function test_create(): void
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
        $this->assertNotNull($user);
        $this->assertEquals('Tobi Olanitori', $user->full_name);
        $this->assertEquals('tobiolanitori@gmail.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        Event::assertDispatched(Registered::class);
    }

    public function test_create_with_no_email()
    {
        $this->expectException(BadRequestException::class);

        $userRepository = new UserRepository();

        $userRepository->createUser([
            'full_name' => 'Tobi Olanitori',
            'password' => 'password123'
        ]);
    }
}
