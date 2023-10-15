<?php

namespace Tests\Feature;

use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * 1. Test input validation
     * 2. Test password is hashed
     * 3. Test Token created
     * 4. Test user is created
     */
    public function test_register_with_bad_credentials(): void
    {
        $this->expectException(UnprocessableException::class);

        $response = $this->withoutExceptionHandling()
            ->postJson('/api/auth/register', [
                'name' => 'Sally'
            ]);
    }

    /**
     * Feature test for create user is implemented in the User test.
     * @see /Tests/UserTest
     */
    public function test_register(): void
    {
        $this->withoutExceptionHandling();

        $credentials = [
            'full_name' => 'Test User',
            'email' => 'testuser20@email.com',
            'password' => 'TestUserPassword1.',
            'password_confirmation' => 'TestUserPassword1.'
        ];

        // $this->mock(UserRepository::class, function (MockInterface $mock) {

        //     $values = [
        //         'full_name' => 'Test User',
        //         'email' => 'testuser20@email.com',
        //         'password' => 'TestUserPassword1.',
        //     ];

        //     $mock->shouldReceive('createdUser')
        //         ->with([
        //             'full_name' => 'Test User',
        //             'email' => 'testuser20@email.com',
        //             'password' => 'TestUserPassword1.',
        //         ])
        //         ->once()
        //         ->andReturnUsing(function ($values) {
        //             $mockedUser = new User();

        //             foreach ($values as $column => $value) {
        //                 $mockedUser->$column = $value;
        //             }

        //             $mockedUser->account_type = 'free';

        //             return $mockedUser;
        //         });
        // });

        $response = $this->postJson('/api/auth/register', $credentials);

        $response->assertCreated();
    }

    public function test_login(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->hasAll(['token', 'user'])
        );
    }

    public function test_login_with_bad_credentials()
    {
        $this->expectException(UnprocessableException::class);

        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $this->withoutExceptionHandling()->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'badpassword'
        ]);
    }
}
