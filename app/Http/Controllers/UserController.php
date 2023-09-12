<?php

namespace App\Http\Controllers;

use App\Exceptions\ServerErrorException;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use PhpParser\Node\Stmt\TryCatch;
use Throwable;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request)
    {
        $userId = Auth::user()->id;

        $validated = $request->validated();

        try {
            User::where('id', $userId)->update($validated);
        }catch(Throwable $e) {
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
