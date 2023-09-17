<?php

namespace App\Http\Controllers;

use App\Enums\OAuthTypeEnum;
use App\Exceptions\BadRequestException;
use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\OAuthRequest;
use App\Http\Requests\RegisterRequest;
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
use Mail;
use URL;

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

        return new JsonResponse($result);
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

        $user = null;

        if (!Auth::attempt(['email' => $oauthUser->email])) {

            $credentials = [
                'full_name' => $oauthUser->name,
                'email' => $oauthUser->email,
            ];
            // Sign up user
            $user = $this->userRepository->createUser($credentials);
        } else {
            // Login user
            $user = Auth::user();
        }

        $token = $user->createToken('access-token')->plainTextToken;

        $result = ['user' => new UserResource($user), 'token' => $token];

        return new JsonResponse($result);
    }

    public function verify(string $user_id, Request $request)
    {

        if (!$request->hasValidSignature()) {
            throw new UnAuthorizedException('Invalid/Expired url provided');
        }

        $user = User::find($user_id);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $redirectUrl = env('CLIENT_URL') . '/dashboard';

        return redirect($redirectUrl);
    }

    public function resendLink()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            throw new BadRequestException("Email already verified.");
        }

        $user = Auth::user();
        Mail::to($user)->send(new EmailVerification($user));

        return response()->json(["msg" => "Email verification link sent on your email id"]);
    }

    public function test()
    {
        $user = User::find("9a1966fa-1e31-46cd-bdbd-31acbd64d27f");
        // event(new Registered($user));
        // return response('', 200);

        $url = URL::temporarySignedRoute(
            'auth.verification.verify', // Route name
            now()->addMinutes(60), // Expiry time
            ['id' => $user->getKey()]
        );

        return $url;
    }
}
