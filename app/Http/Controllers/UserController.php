<?php

namespace App\Http\Controllers;

use App\Exceptions\ServerErrorException;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\Storage;
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

         $logo = $validated['logo'];

         unset($validated['logo']);

         $originalName = $logo->getClientOriginalName();

         $logoUrl = null;
         try {
            $path  = Storage::putFileAs('avatars', $logo, $originalName);

             $logoUrl = env('DO_CDN_SPACE_ENDPOINT').'/'.$path;
         } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
         }

         $validated['logo'] = $logoUrl;

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
