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
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Facades\Socialite;
use Mockery\MockInterface;
use Tests\TestCase;
use URL;

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

        // $response->dd();

        // $response->assertJsonPath('user', UserResource::make($user)->response()->getData(true));
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

    public function test_oAuthRedirect(): void
    {
        // Mock the Socialite driver's behavior
        $provider = 'google'; // Replace with the actual provider
        Socialite::shouldReceive('driver')
            ->with($provider)
            ->once()
            ->andReturnSelf();
        Socialite::shouldReceive('stateless')
            ->once()
            ->andReturnSelf();
        Socialite::shouldReceive('redirect')
            ->once()
            ->andReturnSelf();
        Socialite::shouldReceive('getTargetUrl')
            ->once()
            ->andReturn('https://example.com/oauth/redirect-url');

        // Make a request to the oAuthRedirect endpoint
        $response = $this->get('/api/auth/oauth/redirect?provider=' . $provider);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'provider' => $provider,
                'redirect_url' => 'https://example.com/oauth/redirect-url',
            ]);
    }

    public function test_OAuthCallback(): void
    {
        $this->withoutExceptionHandling();

        // Mock the Socialite driver's behavior
        $provider = 'google'; // Replace with the actual provider
        Socialite::shouldReceive('driver')
            ->with($provider)
            ->once()
            ->andReturnSelf();
        Socialite::shouldReceive('stateless')
            ->once()
            ->andReturnSelf();
        Socialite::shouldReceive('user')
            ->once()
            ->andReturn((object)[
                'name' => 'Test User',
                'email' => 'testuser@example.com',
            ]);

        // Create a test user
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
        ]);


        $response = $this->postJson('/api/auth/oauth/callback', [
            'provider' => $provider,
            'code' => '123'
        ]);

        // Assert the response
        $response->assertStatus(200);
    }

    public function test_verify(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Generate a signed URL for email verification
        $url = URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(15),
            ['id' => $user->getKey()]
        );

        // Simulate a request to your verification endpoint with the signed URL
        $response = $this->get($url);

        // Assert the response
        $response->assertStatus(302);

        // Assert that the user's email is now verified
        $this->assertNotNull(User::find($user->id)->email_verified_at);
    }

    public function test_verify_with_invalid_signature(): void
    {
        $this->expectException(UnAuthorizedException::class);

        // Generate a signed URL with an invalid signature
        $url = URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(15),
            ['id' => 'invalid_user']
        );

        // Simulate a request to your verification endpoint with the signed URL
        $response = $this->withoutExceptionHandling()->get($url);

        // Assert the response
        $response->assertJson(['message' => 'Invalid/Expired url provided']);
    }
}
