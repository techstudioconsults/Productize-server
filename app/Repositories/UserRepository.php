<?php

namespace App\Repositories;

use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnprocessableException;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;


class UserRepository
{
    public function createUser(array $credentials): User
    {
        $user = new User();

        if (!isset($credentials['email'])) {
            throw new BadRequestException('No Email Provided');
        }

        $isValid = [
            'email',
            'full_name',
            'alt_email',
            'username',
            'phone_number',
            'bio',
            'password',
            'logo',
            'twitter_account',
            'facebook_account',
            'youtube_account'
        ];

        // Remove invalid keys from credentials
        $credentials = Arr::only($credentials, $isValid);

        foreach ($credentials as $column => $value) {
            $user->$column = $value;
        }

        $user->account_type = 'free_trial';

        $user->save();

        return $user;
    }

    /**
     * @param filter - is the table column to be used to query
     * @param value - is the value of the column for the user
     * @param updatables - is an associative array of items to be updated
     */
    public function update(string $filter, string $value, array $updatables): User
    {
        // Ensure the user is not attempting to update the user's email
        if (array_key_exists('email', $updatables)) throw new BadRequestException("Column 'email' cannot be updated");

        if (!Schema::hasColumn((new User)->getTable(), $filter)) {
            throw new UnprocessableException("Column '$filter' does not exist in the User table.");
        }

        /**
         * Exclude the filter column from the updatables
         * Preventing coder from updating the filter used to make the update
         * Now, the filter can be safely used to retrieve user after update
         */
        $filteredUpdatables = array_diff_key($updatables, [$filter => null]);

        $user = User::where($filter, $value)->firstOrFail();

        $user->update($filteredUpdatables);

        // Retrieve and return the updated User instance
        return $user;
    }

    /**
     * Use guarded update for columns that are not available for mass assignment.
     * @param email - User email
     * @param column - column to be updated
     * @param value - value of column to be updated
     */
    public function guardedUpdate(string $email, string $column, string $value): User
    {
        if ($column === "email") throw new BadRequestException("Column 'email' cannot be updated");

        if (!Schema::hasColumn((new User)->getTable(), $column)) {
            throw new UnprocessableException("Column '$column' does not exist in the User table.");
        }

        $user = User::where('email', $email)->firstOr(function () use ($email, $column, $value) {
            Log::critical('Guarded update on user failed', ['email' => $email, $column => $value]);
            throw new NotFoundException("user with '$email' not found");
        });

        $user->$column = $value;
        $user->save();

        return $user;
    }

    /**
     * @return int - Total number of products sold
     */
    public function getTotalSales(
        User $user,
        ?string $start_date = null,
        ?string $end_date = null
    ): int {
        $orders = $user->orders();

        if ($start_date && $end_date) {
            $validator = Validator::make([
                'start_date' => $start_date,
                'end_date' => $end_date
            ], [
                'start_date' => 'date',
                'end_date' => 'date'
            ]);

            if ($validator->fails()) {
                throw new UnprocessableException($validator->errors()->first());
            }

            $orders->whereBetween('orders.created_at', [$start_date, $end_date]);
        }

        return $orders->count();
    }

    /**
     * total sales * price
     * @return int - Total revenue generated by the user
     */
    public function getTotalRevenues(
        User $user,
        ?string $start_date = null,
        ?string $end_date = null
    ): int {
        $orders =  $user->orders();

        if ($start_date && $end_date) {
            $validator = Validator::make([
                'start_date' => $start_date,
                'end_date' => $end_date
            ], [
                'start_date' => 'date',
                'end_date' => 'date'
            ]);

            if ($validator->fails()) {
                throw new UnprocessableException($validator->errors()->first());
            }

            $orders->whereBetween('orders.created_at', [$start_date, $end_date]);
        }

        return $orders->sum('total_amount');
    }

    public function getTotalCustomers(
        User $user,
        ?string $start_date = null,
        ?string $end_date = null
    ): int {
        $customers = $user->customers();

        if ($start_date && $end_date) {
            $validator = Validator::make([
                'start_date' => $start_date,
                'end_date' => $end_date
            ], [
                'start_date' => 'date',
                'end_date' => 'date'
            ]);

            if ($validator->fails()) {
                throw new UnprocessableException($validator->errors()->first());
            }

            $customers->whereBetween('created_at', [$start_date, $end_date]);
        }

        return $customers->count();
    }


    /**
     * profileCompletedAt
     *
     * @param  User $user
     * @return void
     */
    public function profileCompletedAt(User $user)
    {
        /**
         * Make a collection of profile properties to be tracked
         * check if they are null
         * If all not null and and profile_completed_at is also not null
         * set profile_completed_at to current date.
         */
        $collection = collect([
            $user->username,
            $user->phone_number,
            $user->bio,
            $user->logo,
            $user->twitter_account,
            $user->facebook_account,
            $user->youtube_account,
        ]);

        $un_filled = $collection->whereNull();

        if ($un_filled->isEmpty() && !$user->profile_completed_at) {
            $user->profile_completed_at = Carbon::now();
            $user->save();
        }
    }
}
