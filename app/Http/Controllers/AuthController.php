<?php

namespace App\Http\Controllers;

use App\Enums\OAuthTypeEnum;
use App\Exceptions\BadRequestException;
use App\Exceptions\UnprocessableException;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\OAuthRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\EmailVerification;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Laravel\Socialite\Facades\Socialite;
use Mail;

class AuthController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository
    ) {
    }

    private function createUser($credentials)
    {
        $user = User::create([
            'full_name' => $credentials['full_name'],
            'email' => $credentials['email'],
            'password' => $credentials['password']
        ]);

        event(new Registered($user));

        return $user;
    }

    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();

        $result = DB::transaction(function () use ($validatedData) {

            $user = $this->createUser($validatedData);

            $token = $user->createToken('access-token')->plainTextToken;

            return ['user' => $user, 'token' => $token];
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
        // $request->session()->regenerate();
        $user = Auth::user();
        $token = $user->createToken('access-token')->plainTextToken;

        $result = ['user' => $user, 'token' => $token];

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

        $auth_code = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response(['code' => $auth_code], 200)
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
            $user = $this->createUser($credentials);
        } else {
            // Login user
            $user = Auth::user();
        }

        $token = $user->createToken('access-token')->plainTextToken;

        $result = ['user' => $user, 'token' => $token];

        return new JsonResponse($result);
    }

    public function verificationLink()
    {
        $user = Auth::user();
        Mail::to($user)->send(new EmailVerification($user));

        return new JsonResponse(['user' => $user, 'message' => 'email sent']);
    }

    public function emailVerification()
    {
    }

    public function test()
    {
        $user = User::find("9a188fda-69a2-4af6-ae7a-059d3733bd26");
        event(new Registered($user));
        return response('', 200);
    }
}
