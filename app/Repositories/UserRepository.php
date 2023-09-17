<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserRepository
{
    public function createUser()
    {
        
    }

    public function update(string $column, string $value, array $array)
    {
        return User::where($column, $value)->update($array);
    }

    /**
     * Use guarded update for columns that are not fillable for mass assignment
     */
    public function guardedUpdate(string $email, string $column, string $value)
    {
        $user = User::where('email', $email)->firstOr(function () use ($email, $column, $value) {
            Log::critical('Guarded update on user failed', ['email' => $email, $column => $value]);
            abort(500);
        });

        $user->$column = $value;
        $user->save();

        return $user;
    }
}
