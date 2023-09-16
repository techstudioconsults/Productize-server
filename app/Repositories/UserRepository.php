<?php

namespace App\Repositories;

use App\Models\User;
use Log;

class UserRepository
{
    public function createUser()
    {
    }

    public function update(string $key, string $value, array $array)
    {
        return User::where($key, $value)->update($array);
    }

    public function guardedUpdate(string $email, string $item, string $value)
    {
        $user = User::where('email', $email)->firstOr(function () use ($email) {
            Log::critical('Guarded update on user failed', ['email' => $email]);
            abort(500);
        });

        $user->$item = $value;
        $user->save();

        return $user;
    }
}
