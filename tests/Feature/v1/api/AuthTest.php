<?php

namespace Tests\Feature;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ServerErrorException;
use App\Exceptions\TooManyRequestException;
use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Mail\EmailVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Socialite\Facades\Socialite;
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

    public function test_resendLink()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(15),
            ['id' => $user->getKey()]
        );

        $this->assertFalse($user->hasVerifiedEmail());

        $response = $this->actingAs($user, 'web')
            ->withoutExceptionHandling()
            ->get('/api/auth/email/resend');

        $mailable = new EmailVerification($user);

        $response->assertStatus(200);

        // $mailable->assertTo($user);

        $mailable->assertSeeInHtml($user->full_name);

        $mailable->assertSeeInHtml($url);

        Mail::assertSent(EmailVerification::class);
    }

    public function test_resendLink_to_verified_user()
    {
        $this->expectException(BadRequestException::class);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->markEmailAsVerified();

        $this->actingAs($user, 'web')
            ->withoutExceptionHandling()
            ->get('/api/auth/email/resend');
    }

    public function test_forgotPassword_success(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Password::shouldReceive('broker')
            ->once()
            ->andReturnSelf();
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_LINK_SENT);

        $response = $this->withoutExceptionHandling()
            ->postJson('/api/auth/forgot-password', ['email' => $user->email]);

        $response->assertStatus(200);

        // Assert the response is a JsonResponse with a success message
        $this->assertJsonStringEqualsJsonString('{"message":"Password reset email sent successfully"}', $response->getContent());
    }

    public function test_forgotPassword_user_not_found(): void
    {
        // Expect a NotFoundException to be thrown
        $this->expectException(NotFoundException::class);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Password::shouldReceive('broker')
            ->once()
            ->andReturnSelf();
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::INVALID_USER);

        $this->withoutExceptionHandling()
            ->postJson('/api/auth/forgot-password', ['email' => $user->email]);
    }

    public function test_forgotPassword_reset_throttled(): void
    {
         // Expect a NotFoundException to be thrown
         $this->expectException(TooManyRequestException::class);

         $user = User::factory()->create([
             'email_verified_at' => now(),
         ]);

         Password::shouldReceive('broker')
             ->once()
             ->andReturnSelf();
         Password::shouldReceive('sendResetLink')
             ->once()
             ->andReturn(Password::RESET_THROTTLED);

         $this->withoutExceptionHandling()
             ->postJson('/api/auth/forgot-password', ['email' => $user->email]);
    }

    public function test_forgotPassword_server_error(): void
    {
         // Expect a NotFoundException to be thrown
         $this->expectException(ServerErrorException::class);

         $user = User::factory()->create([
             'email_verified_at' => now(),
         ]);

         Password::shouldReceive('broker')
             ->once()
             ->andReturnSelf();
         Password::shouldReceive('sendResetLink')
             ->once()
             ->andReturn('unknown_response');

         $this->withoutExceptionHandling()
             ->postJson('/api/auth/forgot-password', ['email' => $user->email]);
    }
}
