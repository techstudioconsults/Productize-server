<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function createUser()
    {

    }

    public function update(string $key, string $value, array $array)
    {
        return User::where($key, $value)->update($array);
    }
}
