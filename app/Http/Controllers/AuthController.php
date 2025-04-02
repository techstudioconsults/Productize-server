<?php

namespace App\Http\Controllers;

use App\Enums\OAuthTypeEnum;
use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ServerErrorException;
use App\Exceptions\TooManyRequestException;
use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\OAuthRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Mail\EmailVerification;
use App\Mail\PasswordChanged;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use Mail;
use Str;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 25-05-2024
 *
 * Route handler methods for Authentication and Authorization
 */
class AuthController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Handle the user registration request.
     *
     * This method processes a user registration request, validates the input data, creates a new user,
     * generates an access token for the user, and returns a JSON response containing the user details
     * and the access token. The user creation and token generation are performed within a database
     * transaction to ensure data integrity. After successful registration, a `Registered` event is triggered.
     *
     * @param  \App\Http\Requests\RegisterRequest  $request  The request object containing the registration data.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the newly registered user and the access token.
     *
     * @throws \App\Exceptions\UnprocessableException If the validation of the request data fails.
     * @throws \Exception If there is an error during the database transaction.
     */
    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();

        $result = DB::transaction(function () use ($validatedData) {

            $user = $this->userRepository->create($validatedData);

            // Create a toke with role "user"
            $token = $user->createToken('access-token', ['role:user'])->plainTextToken;

            // Trigger register event
            event(new Registered($user));

            return ['user' => $user, 'token' => $token];
        });

        $user = $result['user'];
        $token = $result['token'];

        $response = ['user' => new UserResource($user), 'token' => $token];

        return new JsonResponse($response, 201);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Handle the user login request.
     *
     * This method processes a user login request, validates the provided credentials, and attempts to authenticate the user.
     * If the authentication is successful, it generates an access token for the user with role ability and returns a JSON response containing
     * the authenticated user details and the access token. If the authentication fails, it throws an UnprocessableException
     * with an error message indicating incorrect credentials.
     *
     * To access the login endpoint, your SPA's "login" request should first make a request to the /sanctum/csrf-cookie endpoint
     * to initialize CSRF protection for the application. It is recommended to chain waterfall requests, combining the CSRF and login
     * endpoints.
     *
     * @param  \App\Http\Requests\LoginRequest  $request  The request object containing the login credentials.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the authenticated user and the access token.
     *
     * @throws \App\Exceptions\UnprocessableException If the provided credentials are incorrect.
     * @throws \App\Exceptions\UnprocessableException If the validation of the request data fails.
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $remember = $credentials['remember'] ?? false;
        unset($credentials['remember']);

        if (! Auth::attempt($credentials, $remember)) {
            throw new UnprocessableException('The Provided credentials are not correct');
        }

        $user = Auth::user();

        $role = strtolower($user->role);

        // Check the user role and add to sanctum's token ability in lower case
        $ability = ["role:$role"];

        $token = $user->createToken('access-token', $ability)->plainTextToken;

        $result = ['user' => new UserResource($user), 'token' => $token];

        return new JsonResponse($result);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Redirect the user to the OAuth provider for authentication.
     *
     * This method handles the OAuth redirection process, where the user is redirected to the specified OAuth provider
     * for authentication. It validates the OAuth provider specified in the request query parameters and generates
     * a redirect URL using the Socialite package. If the specified provider is invalid, it throws an UnprocessableException
     * with an error message indicating the validation failure.
     *
     * @param  Request  $request  The HTTP request containing query parameters:
     *                            - provider: (optional) OAuth Provider (enum OAuthTypeEnum).
     * @return \Illuminate\Http\Response The HTTP response containing the provider and the redirect URL.
     *
     * @throws \App\Exceptions\UnprocessableException If the OAuth provider is missing or invalid.
     */
    public function oAuthRedirect(Request $request)
    {
        $provider = $request->query('provider');

        $validator = Validator::make(['provider' => $provider], [
            'provider' => ['required', new Enum(OAuthTypeEnum::class)],

        ]);

        if ($validator->fails()) {
            throw new UnprocessableException($validator->errors()->first());
        }

        $redirect_url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response(['provider' => $provider, 'redirect_url' => $redirect_url], 200)
            ->header('Content-Type', 'application/json')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Handle the callback after OAuth authentication.
     *
     * This method handles the callback after the user has been authenticated via OAuth. It validates the OAuth request
     * data, retrieves the OAuth user information using Socialite, and attempts to find or create a local user based on
     * the OAuth user's email. If a user with the email does not exist, a new user is registered. If the user exists,
     * they are logged in. Finally, a JSON response containing the user information and access token is returned.
     *
     * @param  \App\Http\Requests\OAuthRequest  $request  The validated OAuth request object.
     * @return JsonResponse The JSON response containing the user information and access token.
     *
     * @throws \App\Exceptions\BadRequestException If an error occurs during the OAuth authentication process.
     */
    public function oAuthCallback(Request $request)
    {
        // update
        $provider = $request->input('provider');

        try {
            // Exchange the authorization code for an access token
            $oauthUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Throwable $th) {
            Log::alert('Social Auth Failure', ['message' => $th->getMessage()]);
            throw new BadRequestException('Authentication Error');
        }

        $user = User::firstWhere('email', $oauthUser->email);

        if (! $user) {
            $credentials = [
                'full_name' => $oauthUser->name,
                'email' => $oauthUser->email,
            ];
            // Sign up user
            $user = $this->userRepository->create($credentials);

            // Send register email
            event(new Registered($user));
        } else {
            // Login user
            Auth::login($user);

            $user = Auth::user();
        }

        $role = strtolower($user->role);

        // Check the user role and add to sanctum's token ability in lower case
        $ability = ["role:$role"];

        $token = $user->createToken('access-token', $ability)->plainTextToken;

        $result = ['user' => new UserResource($user), 'token' => $token];

        return new JsonResponse($result);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Verify the user's email address.
     *
     * This method verifies the user's email address using a signed URL provided in the request. If the URL is invalid
     * or expired, it throws an UnAuthorizedException. It then retrieves the user based on the provided user ID,
     * marks their email address as verified if it's not already verified, and redirects the user to the dashboard page.
     *
     * @param  string  $user_id  The ID of the user whose email is being verified.
     * @param  Request  $request  The HTTP request object containing the signed URL.
     * @return \Illuminate\Http\RedirectResponse The redirect response to the dashboard page.
     *
     * @throws \App\Exceptions\UnAuthorizedException If the provided URL is invalid or expired.
     * @throws \App\Exceptions\NotFoundException If the user does not exist.
     */
    public function verify(string $user_id, Request $request)
    {
        if (! $request->hasValidSignature()) {
            return view('pages.auth.expired-url');
        }

        $user = User::find($user_id);

        if (! $user) {
            throw new NotFoundException('User Does Not Exist');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $redirectUrl = config('app.client_url').'/dashboard/home';

        return redirect($redirectUrl);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Resend the email verification link.
     *
     * This method is responsible for resending the email verification link to the authenticated user if their email address
     * has not been verified yet. It first checks if the user's email has already been verified; if so, it throws a
     * BadRequestException indicating that the email is already verified. Otherwise, it retrieves the authenticated user,
     * sends the email verification link to their email address, and returns a JSON response indicating that the link
     * has been sent.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response indicating that the email verification link has been sent.
     *
     * @throws \App\Exceptions\BadRequestException If the user's email is already verified.
     */
    public function resendLink()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            throw new BadRequestException('Email already verified.');
        }

        $user = Auth::user();
        Mail::to($user)->send(new EmailVerification($user));

        return new JsonResponse([
            'message' => 'Email verification link sent on your email address',
        ]);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Send password reset link via email.
     *
     * This method is responsible for sending a password reset link via email to the provided email address.
     * It implements Laravel's password reset functionality manually, following the documentation for Laravel 10.
     * It extracts the email address from the request and uses Laravel's Password Broker to send the reset link.
     * Depending on the response from the Password Broker, it returns a JSON response with a success message if the
     * reset link is sent successfully, or throws appropriate exceptions for cases where the user is not found, the reset
     * request is throttled, or the email could not be sent for some other reason.
     *
     * @param  \App\Http\Requests\ForgotPasswordRequest  $request  The request containing the user's email address.
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the password reset email.
     *
     * @throws \App\Exceptions\NotFoundException If the user associated with the provided email address is not found.
     * @throws \App\Exceptions\TooManyRequestException If the reset request is throttled due to too many attempts.
     * @throws \App\Exceptions\ServerErrorException If the password reset email could not be sent for some other reason.
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {

        $email = $request->only('email');

        /**
         * Implementing Laravel 10 Password reset functionality manually
         * https://laravel.com/docs/10.x/passwords
         */
        $response = Password::broker()->sendResetLink($email);

        if ($response == Password::RESET_LINK_SENT) {
            return new JsonResponse(['message' => 'Password reset email sent successfully']);
        } elseif ($response == Password::INVALID_USER) {
            throw new NotFoundException('User not found');
        } elseif ($response == Password::RESET_THROTTLED) {
            throw new TooManyRequestException;
        } else {
            throw new ServerErrorException('Password reset email could not be sent');
        }
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Reset user password.
     *
     * This method is responsible for resetting a user's password using the provided token, email address, new password,
     * and password confirmation. It implements Laravel's password reset functionality manually, following the documentation
     * for Laravel 10. It extracts the necessary credentials from the request and defines a closure function to force change
     * the user's password. It then uses Laravel's Password Broker to reset the password based on the provided credentials
     * and the custom force change password closure. Depending on the response from the Password Broker, it returns a JSON
     * response with a success message if the password is reset successfully, or throws exceptions for cases where the token
     * is invalid or the password reset fails for some other reason.
     *
     * @param  \App\Http\Requests\ResetPasswordRequest  $request  The request containing the user's email, password, password confirmation, and reset token.
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the password reset.
     *
     * @throws \App\Exceptions\UnAuthorizedException If the provided token is invalid.
     * @throws \App\Exceptions\BadRequestException If the password reset fails for some other reason.
     */
    public function ResetPassword(ResetPasswordRequest $request)
    {
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $user = $this->userRepository->findOne(['email' => $credentials['email']]);

        if (! $user) {
            throw new NotFoundException('User Not Found');
        }

        $forceChangePassword = function (User $user, string $password) {
            $user->forceFill([
                'password' => $password,
            ])->setRememberToken(Str::random(60));

            $user->save();
        };
        /**
         * Implementing Laravel 10 Password reset functionality manually
         * https://laravel.com/docs/10.x/passwords
         */
        $res = Password::reset($credentials, $forceChangePassword);

        if ($res === Password::PASSWORD_RESET) {

            // Send Email to user
            Mail::send(new PasswordChanged($user));

            return new JsonResponse(['message' => 'Password Reset Successful']);
        } elseif ($res === Password::INVALID_TOKEN) {
            throw new UnAuthorizedException('Invalid Token');
        } else {
            throw new BadRequestException('Password Reset Failed');
        }
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Logout the authenticated user.
     *
     * This method is responsible for logging out the authenticated user by revoking the current access token associated
     * with the user. It takes a request containing the authenticated user, deletes the current access token associated
     * with that user, and returns a JSON response indicating a successful logout.
     *
     * @param  \Illuminate\Http\Request  $request  The request containing the authenticated user.
     * @return \Illuminate\Http\JsonResponse The JSON response indicating a successful logout.
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return new JsonResponse(['message' => 'Logout Successful']);
    }
}
