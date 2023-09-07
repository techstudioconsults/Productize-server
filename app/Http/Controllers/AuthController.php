<?php

namespace App\Http\Controllers;

use App\Exceptions\UnprocessableException;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();

        $result = DB::transaction(function () use ($validatedData) {
            $user = User::create([
                'full_name' => $validatedData['full_name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password'])
            ]);
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
        $user = Auth::user();
        $token = $user->createToken('access-token')->plainTextToken;

        $result = ['user' => $user, 'token' => $token];

        return new JsonResponse($result);
    }
}
