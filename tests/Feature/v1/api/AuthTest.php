<?php

namespace Tests\Feature;

use App\Exceptions\BadRequestException;
use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Mail\EmailVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
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

    // public function test_forgotPassword_success(): void
    // {
    //     $user = User::factory()->create([
    //         'email_verified_at' => now(),
    //     ]);

    //     $user->markEmailAsVerified();


    //     // Create a test request with a valid email
    //     // $request = new ForgotPasswordRequest();
    //     // $request->merge(['email' => 'test@example.com']);

    //     // Mock the Password broker to return a success response
    //     Password::shouldReceive('broker')->andReturn(
    //         new class {
    //             public function sendResetLink($credentials) {
    //                 return Password::RESET_LINK_SENT;
    //             }
    //         }
    //     );

    //     // Create an instance of your controller
    //     $controller = new YourController(); // Replace with the actual controller class

    //     // Call the forgotPassword method
    //     $response = $controller->forgotPassword($request);

    //     // Assert the response is a JsonResponse with a success message
    //     $this->assertInstanceOf(JsonResponse::class, $response);
    //     $this->assertJsonStringEqualsJsonString('{"message":"Password reset email sent successfully"}', $response->getContent());
    // }

    // public function test_forgotPassword_user_not_found(): void
    // {
    //     // Create a test request with an invalid email
    //     $request = new ForgotPasswordRequest();
    //     $request->merge(['email' => 'nonexistent@example.com']);

    //     // Mock the Password broker to return a user not found response
    //     Password::shouldReceive('broker')->andReturn(
    //         new class {
    //             public function sendResetLink($credentials) {
    //                 return Password::INVALID_USER;
    //             }
    //         }
    //     );

    //     // Create an instance of your controller
    //     $controller = new YourController(); // Replace with the actual controller class

    //     // Expect a NotFoundException to be thrown
    //     $this->expectException(NotFoundException::class);

    //     // Call the forgotPassword method
    //     $controller->forgotPassword($request);
    // }

    // public function test_forgotPassword_reset_throttled(): void
    // {
    //     // Create a test request with a valid email
    //     $request = new ForgotPasswordRequest();
    //     $request->merge(['email' => 'test@example.com']);

    //     // Mock the Password broker to return a reset throttled response
    //     Password::shouldReceive('broker')->andReturn(
    //         new class {
    //             public function sendResetLink($credentials) {
    //                 return Password::RESET_THROTTLED;
    //             }
    //         }
    //     );

    //     // Create an instance of your controller
    //     $controller = new YourController(); // Replace with the actual controller class

    //     // Expect a TooManyRequestException to be thrown
    //     $this->expectException(TooManyRequestException::class);

    //     // Call the forgotPassword method
    //     $controller->forgotPassword($request);
    // }

    // public function test_forgotPassword_server_error(): void
    // {
    //     // Create a test request with a valid email
    //     $request = new ForgotPasswordRequest();
    //     $request->merge(['email' => 'test@example.com']);

    //     // Mock the Password broker to return an unknown response
    //     Password::shouldReceive('broker')->andReturn(
    //         new class {
    //             public function sendResetLink($credentials) {
    //                 return 'unknown_response';
    //             }
    //         }
    //     );

    //     // Create an instance of your controller
    //     $controller = new YourController(); // Replace with the actual controller class

    //     // Expect a ServerErrorException to be thrown
    //     $this->expectException(ServerErrorException::class);

    //     // Call the forgotPassword method
    //     $controller->forgotPassword($request);
    // }
}
