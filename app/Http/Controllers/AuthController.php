<?php

namespace App\Http\Controllers;

use App\Enums\OAuthTypeEnum;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
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
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Password;
use Mail;
use Str;

class AuthController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository
    ) {
    }

    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();

        $result = DB::transaction(function () use ($validatedData) {

            $user = $this->userRepository->createUser($validatedData);

            $token = $user->createToken('access-token')->plainTextToken;

            return ['user' => new UserResource($user), 'token' => $token];
        });

        return new JsonResponse($result, 201);
    }

    /**
     * To access login endpoint, your SPA's "login" request should first make a request to the /sanctum/csrf-cookie endpoint to initialize CSRF protection for the application
     * It is advised to create water fall requests chaining the csrf and login endpoints.
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $remember = $credentials['remember'] ?? false;
        unset($credentials['remember']);

        if (!Auth::attempt($credentials, $remember)) {
            throw new UnprocessableException('The Provided credentials are not correct');
        }

        $user = Auth::user();
        $token = $user->createToken('access-token')->plainTextToken;

        $result = ['user' => new UserResource($user), 'token' => $token];

        return new JsonResponse($result);
    }

    public function oAuthRedirect(Request $request)
    {
        $provider = $request->query('provider');

        $validator = Validator::make(['provider' => $provider], [
            'provider' => ['required', new Enum(OAuthTypeEnum::class)]

        ]);

        if ($validator->fails()) {
            throw new UnprocessableException($validator->errors()->first());
        }

        $redirect_url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response(['provider' => $provider, 'redirect_url' => $redirect_url], 200)
            ->header('Content-Type', 'application/json')
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function oAuthCallback(OAuthRequest $request)
    {
        $validated = $request->validated();

        $oauthUser = null;
        try {
            $oauthUser = Socialite::driver($validated['provider'])->stateless()->user();
        } catch (\Throwable $th) {
            throw new BadRequestException($th->getMessage());
        }

        $user = User::firstWhere('email', $oauthUser->email);

        if (!$user) {
            $credentials = [
                'full_name' => $oauthUser->name,
                'email' => $oauthUser->email,
            ];
            // Sign up user
            $user = $this->userRepository->createUser($credentials);
        } else {
            // Login user
            Auth::login($user);

            $user = Auth::user();
        }

        $token = $user->createToken('access-token')->plainTextToken;

        $result = ['user' => new UserResource($user), 'token' => $token];

        return new JsonResponse($result);
    }

    public function verify(string $user_id, Request $request)
    {

        /**
         * Dont throw an error, render error page instead.
         */
        if (!$request->hasValidSignature()) {
            throw new UnAuthorizedException('Invalid/Expired url provided');
        }

        $user = User::find($user_id);

        if (!$user) {
            throw new UnAuthorizedException('Invalid/Expired url provided');
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $redirectUrl = config('app.client_url') . '/dashboard';

        return redirect($redirectUrl);
    }
    // teting

    public function resendLink()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            throw new BadRequestException("Email already verified.");
        }

        $user = Auth::user();
        Mail::to($user)->send(new EmailVerification($user));

        return response()->json(["msg" => "Email verification link sent on your email id"]);
    }

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
        } else if ($response == Password::INVALID_USER) {
            throw new NotFoundException('User not found');
        } else if ($response == Password::RESET_THROTTLED) {
            throw new TooManyRequestException();
        } else {
            throw new ServerErrorException('Password reset email could not be sent');
        }
    }

    public function ResetPassword(ResetPasswordRequest $request)
    {
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $forceChangePassword = function (User $user, string $password) {
            $user->forceFill([
                'password' => $password
            ])->setRememberToken(Str::random(60));

            $user->save();
        };
        /**
         * Implementing Laravel 10 Password reset functionality manually
         * https://laravel.com/docs/10.x/passwords
         */
        $res = Password::reset($credentials, $forceChangePassword);

        if ($res ===  Password::PASSWORD_RESET) {
            return new JsonResponse(['message' => "Password Reset Successful"]);
        } else if ($res === Password::INVALID_TOKEN) {
            throw new UnAuthorizedException('Invalid Token');
        } else {
            throw new BadRequestException('Password Reset Failed');
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return new JsonResponse(['message' => "Logout Successful"]);
    }
}
