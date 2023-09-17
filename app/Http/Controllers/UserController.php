<?php

namespace App\Http\Controllers;

use App\Exceptions\ServerErrorException;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\User;
use App\Repositories\UserRepository;
use Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UserController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository
    ) {
    }


    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        //
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

                $logoUrl = env('DO_CDN_SPACE_ENDPOINT') . '/' . $path;
            } catch (\Throwable $th) {
                throw new ServerErrorException($th->getMessage());
            }

            $validated['logo'] = $logoUrl;
        }


        try {
            $this->userRepository->update('id', $userId, $validated);
        } catch (Throwable $e) {
            throw new ServerErrorException($e->getMessage());
        }

        $user = User::find($userId);

        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
