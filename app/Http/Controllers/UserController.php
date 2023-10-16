<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ServerErrorException;
use App\Exceptions\UnprocessableException;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use App\Repositories\UserRepository;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository,
        protected ProductRepository $productRepository
    ) {
    }

    public function show(Request $request)
    {
        $user = $request->user();

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request)
    {
        $userId = Auth::user()->id;

        $validated = $request->validated();


        if (isset($validated['logo'])) {
            $logo = $validated['logo'];

            unset($validated['logo']);

            $originalName = $logo->getClientOriginalName();

            $logoUrl = null;

            try {
                $path  = Storage::putFileAs('avatars', $logo, $originalName);

                $logoUrl = config('filesystems.disks.spaces.cdn_endpoint') . '/' . $path;
            } catch (\Throwable $th) {
                throw new ServerErrorException($th->getMessage());
            }

            $validated['logo'] = $logoUrl;
        }


        try {
            $user = $this->userRepository->update('id', $userId, $validated);

            return new UserResource($user);
        } catch (Throwable $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        }
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'new_password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols()
            ],
        ]);

        if ($validator->fails()) {
            throw new UnprocessableException($validator->errors()->first());
        }

        $validated = $validator->validated();

        $user = Auth::user();

        if (!Hash::check($validated['password'], $user->password)) {
            throw new BadRequestException('Incorrect Password');
        }

        $user = $this->userRepository->guardedUpdate($user->email, 'password', $validated['new_password']);

        return new UserResource($user);
    }
}
