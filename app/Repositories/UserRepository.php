<?php

namespace App\Repositories;

use App\Exceptions\BadRequestException;
use App\Exceptions\UnprocessableException;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class UserRepository
{
    public function __construct()
    {
    }

    public function createUser(array $credentials): User
    {
        $user = new User;

        if (!isset($credentials['email'])) {
            throw new BadRequestException('No Email Provided');
        }

        foreach ($credentials as $column => $value) {
            $user->$column = $value;
        }

        $user->save();

        event(new Registered($user));

        return $user;
    }

    /**
     * @param filter - is the table column to be used to query
     * @param value - is the value of the column for the user
     * @param updatables - is an associative array of items to be updated
     */
    public function update(string $filter, string $value, array $updatables): User
    {
        if (!Schema::hasColumn((new User)->getTable(), $filter)) {
            throw new UnprocessableException("Column '$filter' does not exist in the User table.");
        }

        /**
         * Exclude the filter column from the updatables
         * Preventing coder from updating the filter used to make the update
         * Now, the filter can be safely used to retrieve user after update
         */
        $filteredUpdatables = array_diff_key($updatables, [$filter => null]);

        User::where($filter, $value)->update($filteredUpdatables);

        // Retrieve and return the updated User instance
        return User::where($filter, $value)->firstOrFail();
    }

    /**
     * Use guarded update for columns that are not available for mass assignment.
     * @param email - User email
     * @param column - column to be updated
     * @param value - value of column to be updated
     */
    public function guardedUpdate(string $email, string $column, string $value): User
    {
        if (!Schema::hasColumn((new User)->getTable(), $column)) {
            throw new UnprocessableException("Column '$column' does not exist in the User table.");
        }

        $user = User::where('email', $email)->firstOr(function () use ($email, $column, $value) {
            Log::critical('Guarded update on user failed', ['email' => $email, $column => $value]);
            abort(500);
        });

        $user->$column = $value;
        $user->save();

        return $user;
    }
}
