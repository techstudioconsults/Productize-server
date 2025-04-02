<?php

namespace Tests\Feature;

use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ServerErrorException;
use App\Exceptions\TooManyRequestException;
use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Http\Resources\UserResource;
use App\Mail\EmailVerification;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $full_name;

    protected $email;

    protected $password;

    private $base_url = '/api/auth';

    protected function setUp(): void
    {
        parent::setUp();

        $this->full_name = 'Tobi Olanitori';
        $this->email = 'tobiolanitori@gmail.com';
        $this->password = 'Password1231.';
    }

    public function test_register_with_bad_credentials(): void
    {
        $this->expectException(UnprocessableException::class);

        $this->withoutExceptionHandling()
            ->postJson($this->base_url.'/register', [
                'name' => 'Sally',
            ]);
    }

    /**
     * Feature test for create user is implemented in the User test.
     *
     * @see /Tests/UserTest
     */
    public function test_register(): void
    {
        Event::fake();

        $credentials = [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password,
        ];

        $response = $this->postJson($this->base_url.'/register', $credentials);

        $response
            ->assertCreated()
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'user',
                    fn (AssertableJson $json) => $json->whereType('id', 'string')
                        ->where('name', $this->full_name)
                        ->where('email', fn (string $email) => str($email)->is($this->email))
                        ->where('account_type', 'free_trial')
                        ->missing('password')
                        ->etc()
                )->has('token')
            );

        Event::assertDispatched(Registered::class);
    }

    public function test_login(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->postJson($this->base_url.'/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Create an instance of UserResource
        $userResource = new UserResource($user);

        // Access the array representation directly
        $userArray = $userResource->jsonSerialize();

        // $response->assertStatus(200);
        $response->assertOk()->assertJsonStructure([
            'token',
            'user' => array_keys($userArray), // Ensure the user structure matches
        ]);
    }

    public function test_login_with_bad_credentials()
    {
        $this->expectException(UnprocessableException::class);

        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $this->withoutExceptionHandling()->postJson($this->base_url.'/login', [
            'email' => $user->email,
            'password' => 'badpassword',
        ]);
    }

    public function test_o_auth_redirect(): void
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
        $response = $this->get($this->base_url.'/oauth/redirect?provider='.$provider);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'provider' => $provider,
                'redirect_url' => 'https://example.com/oauth/redirect-url',
            ]);
    }

    public function test_o_auth_callback_unregistered_user(): void
    {
        Event::fake();

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
            ->andReturn((object) [
                'name' => $this->full_name,
                'email' => $this->email,
            ]);

        $response = $this->get('/api/auth/oauth/callback?provider='.$provider.'&code=12345');

        // Assert the response
        $response
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'user',
                    fn (AssertableJson $json) => $json->whereType('id', 'string')
                        ->where('name', $this->full_name)
                        ->where('email', fn (string $email) => str($email)->is($this->email))
                        ->where('account_type', 'free_trial')
                        ->missing('password')
                        ->etc()
                )->has('token')
            );

        // Event should dispatch for unregistered user.
        Event::assertDispatched(Registered::class);
    }

    public function test_o_auth_callback_login_user(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'email' => $this->email,
            'full_name' => $this->full_name,
            'account_type' => 'free_trial',
        ]);

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
            ->andReturn((object) [
                'name' => $this->full_name,
                'email' => $this->email,
            ]);

        $response = $this->get('/api/auth/oauth/callback?provider='.$provider.'&code=12345');

        // Create an instance of UserResource
        $userResource = new UserResource($user);

        // Access the array representation directly
        $userArray = $userResource->jsonSerialize();

        // $response->assertStatus(200);
        $response->assertOk()->assertJsonStructure([
            'token',
            'user' => array_keys($userArray), // Ensure the user structure matches
        ]);

        // Event should not be dispatched for already registered user.
        Event::assertNotDispatched(Registered::class);
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
        $this->expectException(NotFoundException::class);

        // Generate a signed URL with an invalid signature
        $url = URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(15),
            ['id' => 'invalid_user']
        );

        // Simulate a request to your verification endpoint with the signed URL
        $response = $this->withoutExceptionHandling()->get($url);
    }

    public function test_resend_link()
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

        $mailable->assertSeeInHtml($user->full_name);

        $mailable->assertSeeInHtml($url);

        Mail::assertSent(EmailVerification::class);
    }

    public function test_resend_link_to_verified_user()
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

    public function test_forgot_password_success(): void
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

    public function test_forgot_password_user_not_found(): void
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

    public function test_forgot_password_reset_throttled(): void
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

    public function test_forgot_password_server_error(): void
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

    public function test_reset_password_success(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Mock the Password reset method to return a success response
        Password::shouldReceive('reset')->andReturn(Password::PASSWORD_RESET);

        $credentials = [
            'email' => $user->email,
            'password' => 'New_password1-',
            'password_confirmation' => 'New_password1-',
            'token' => 'd0823a454761c349b9b81234f0a2869f1270237444f5b8e3890876105fab7af6',
        ];

        $response = $this->withoutExceptionHandling()
            ->postJson('/api/auth/reset-password', $credentials);

        $response->assertStatus(200);
        $this->assertJsonStringEqualsJsonString('{"message":"Password Reset Successful"}', $response->getContent());
    }

    public function test_reset_password_invalid_token(): void
    {
        // Expect an UnAuthorizedException to be thrown
        $this->expectException(UnAuthorizedException::class);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Mock the Password reset method to return an invalid token response
        Password::shouldReceive('reset')->andReturn(Password::INVALID_TOKEN);

        $credentials = [
            'email' => $user->email,
            'password' => 'New_password1-',
            'password_confirmation' => 'New_password1-',
            'token' => 'invalid_token',
        ];

        $this->withoutExceptionHandling()
            ->postJson('/api/auth/reset-password', $credentials);
    }

    public function test_reset_password_failure(): void
    {
        // Expect an BadRequestException to be thrown
        $this->expectException(BadRequestException::class);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Mock the Password reset method to return an invalid token response
        Password::shouldReceive('reset')->andReturn('generic_failure');

        $credentials = [
            'email' => $user->email,
            'password' => 'New_password1-',
            'password_confirmation' => 'New_password1-',
            'token' => 'invalid_token',
        ];

        $this->withoutExceptionHandling()
            ->postJson('/api/auth/reset-password', $credentials);
    }
}
