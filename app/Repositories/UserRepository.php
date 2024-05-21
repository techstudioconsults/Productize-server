<?php

/**
 * @author Tobi Olanitori
 * @version 1.0
 * @since 12-05-2024
 */

namespace App\Repositories;

use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnprocessableException;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


class UserRepository extends Repository
{
    public function seed(): void
    {
        User::factory(20)->create();
    }

    public function create(array $credentials): User
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

    public function find(?array $filter): Builder
    {
        $query = User::query();

        if ($filter === null) return $query;

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        // For each filter array, entry, validate presence in model and query
        // foreach ($filter as $key => $value) {
        //     if (Schema::hasColumn('users', $key)) {
        //         $query->where($key, $value);
        //     }
        // }

        return $query;
    }

    public function findByRelation(Model $parent, ?array $filter): Relation
    {
        // Start with the base relation
        $relation = $parent?->users();

        if (!$relation) {
            throw new ApiException(
                "Unable to retrieve related users. The parent model does not have a defined 'users' relationship.",
                500
            );
        }

        if (empty($filter)) return $relation;

        $this->applyDateFilters($relation, $filter);

        foreach ($filter as $key => $value) {
            // Check if the key exists as a column in the orders table
            if (Schema::hasColumn('customers', $key)) {
                // Apply where condition based on the filter
                $relation = $relation->where($key, $value);
            }
        }

        return $relation;
    }

    public function findById(string $id): Model
    {
        return User::find($id);
    }

    public function findOne(array $filter): User
    {
        return User::where($filter)->first();
    }

    public function update(Model $entity, array $updatables): User
    {
        if (!$entity instanceof User) {
            throw new ModelCastException("User", get_class($entity));
        }

        // Ensure the user is not attempting to update the user's email
        if (array_key_exists('email', $updatables)) throw new BadRequestException("Column 'email' cannot be updated");

        // Assign the updates to the corresponding fields of the User instance
        $entity->fill($updatables);

        // Save the updated Customer instance
        $entity->save();

        // Return the updated Customer model
        return $entity;
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

        // Filter by start date and end date.
        if ($start_date && $end_date) {
            $isInvalid = $this->isInValidDateRange($start_date, $end_date);

            if ($isInvalid) throw new UnprocessableException($this->getValidator()->errors()->first());

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
            $isInvalid = $this->isInValidDateRange($start_date, $end_date);

            if ($isInvalid) throw new UnprocessableException($this->getValidator()->errors()->first());

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
            $isInvalid = $this->isInValidDateRange($start_date, $end_date);

            if ($isInvalid) throw new UnprocessableException($this->getValidator()->errors()->first());

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
