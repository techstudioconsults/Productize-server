<?php

/**
 * @author @Intuneteq Tobi Olanitori
 * @version 1.0
 * @since 12-05-2024
 */

namespace App\Repositories;

use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnprocessableException;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * Repository for Order resource
 */
class UserRepository extends Repository
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected CustomerRepository $customerRepository
    ) {
    }

    public function seed(): void
    {
        User::factory(20)->create();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Create a new user with the provided credentials.
     *
     * @param array $credentials The user credentials.
     *                           Required keys: 'email', 'full_name', 'password'.
     *                           Optional keys: 'alt_email', 'username', 'phone_number', 'bio', 'logo',
     *                           'twitter_account', 'facebook_account', 'youtube_account'.
     * @return \App\Models\User The newly created user.
     * @throws \App\Exceptions\BadRequestException If no email is provided in the credentials.
     */
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

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Query orders based on the provided filter.
     *
     * @param array $filter The filter criteria to apply.
     * @return Builder The query builder for orders.
     */
    public function query(array $filter): Builder
    {
        $query = User::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find users based on the provided filter.
     *
     * @param array|null $filter The filter criteria to apply (optional).
     * @return Collection The collection of found users.
     */
    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a user by their ID.
     *
     * @param string $id The ID of the user to find.
     * @return User|null The found user instance, or null if not found.
     */
    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a single user based on the provided filter.
     *
     * @param array $filter The filter criteria to apply.
     * @return User|null The found user instance, or null if not found.
     */
    public function findOne(array $filter): ?User
    {
        return User::where($filter)->first();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Update an entity in the database.
     *
     * @param  Model $entity The user to be updated
     * @param array $updates The array of data containing the fields to be updated.
     * @return User The updated user
     */
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
     * @author @Intuneteq Tobi Olanitori
     *
     * Perform a guarded update on the specified user.
     *
     * This method updates the specified column of a user while guarding against mass assignment vulnerabilities.
     * It ensures that the provided column is valid and not part of the guarded columns.
     * If the column is 'email', it throws a BadRequestException since the email cannot be updated.
     *
     * @param string $email The email of the user to update.
     * @param string $column The column to be updated.
     * @param string $value The new value for the specified column.
     *
     * @return \App\Models\User The updated user instance.
     *
     * @throws \App\Exceptions\BadRequestException If the column to update is 'email'.
     * @throws \App\Exceptions\UnprocessableException If the specified column does not exist in the User table.
     * @throws \App\Exceptions\NotFoundException If the user with the given email is not found.
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
     * @author @Intuneteq Tobi Olanitori
     *
     * Get the total number of products sold by the specified user within the given date range.
     *
     * This method calculates the total number of products sold by the specified user.
     * Optionally, it allows filtering orders based on a start date and an end date.
     *
     * @param \App\Models\User $user The user for whom to calculate the total sales.
     * @param array $filter An associative array of filters to apply to the relation.
     *                      Supported filters include:
     *                      - 'start_date' and 'end_date': Apply a date range filter on the 'created_at' column of the order table.
     *                      - Other key-value pairs will be used as where conditions on the relation.
     *
     * @return int The total number of products sold by the user within the specified date range.
     *
     * @throws \App\Exceptions\UnprocessableException If the provided date range is invalid.
     */
    public function getTotalSales(User $user, ?array $filter = []): int
    {
        return $this->orderRepository->queryRelation($user->orders(), $filter)->count();
    }

    /**
     *  @author @Intuneteq Tobi Olanitori
     *
     * Calculate the total revenue generated by the user within the specified date range.
     *
     * This method calculates the total revenue generated by the user, considering the total amount
     * of each order placed within the given date range.
     *
     * @param \App\Models\User $user The user for whom to calculate the total revenue.
     * @param array $filter (optional) An associative array of filters to apply to the relation.
     *                      Supported filters include:
     *                      - 'start_date' and 'end_date': Apply a date range filter on the 'created_at' column of the order table.
     *                      - Other key-value pairs will be used as where conditions on the relation.
     *
     * @return int The total revenue generated by the user within the specified date range.
     *
     * @throws \App\Exceptions\UnprocessableException If the provided date range is invalid.
     */
    public function getTotalRevenues(User $user, ?array $filter = []): int
    {

        return $this->orderRepository->queryRelation($user->orders(), $filter)->sum('total_amount');
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Calculate the total number of customers associated with the user within the specified date range.
     *
     * This method calculates the total number of customers associated with the user, considering
     * the customers created within the given date range.
     *
     * @param \App\Models\User $user The user for whom to calculate the total number of customers.
     *@param array $filter (optional) An associative array of filters to apply to the relation.
     *                      Supported filters include:
     *                      - 'start_date' and 'end_date': Apply a date range filter on the 'created_at' column of the customer's table.
     *                      - Other key-value pairs will be used as where conditions on the relation.
     *
     * @return int The total number of customers associated with the user within the specified date range.
     *
     * @throws \App\Exceptions\UnprocessableException If the provided date range is invalid.
     */
    public function getTotalCustomers(User $user, ?array $filter = []): int
    {
        return $this->customerRepository->queryRelation($user->customers(), $filter)->count();
    }

    /**
     *  @author @Intuneteq Tobi Olanitori
     *
     * Update the profile completion timestamp for the user if all required profile properties are filled.
     *
     * This method checks if all required profile properties (such as username, phone number, bio, etc.)
     * are not null for the given user. If all required properties are filled and the profile completion
     * timestamp is not already set, it updates the profile_completed_at field to the current date and time.
     *
     * @param \App\Models\User $user The user for whom to update the profile completion timestamp.
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

    /**
     * Get or create a user by email and name.
     *
     * @param string $email
     * @param string|null $name
     * @return User
     */
    public function firstOrCreate(string $email, ?string $name): User
    {
        return User::firstOrCreate(['email' => $email], ['full_name' => $name]);
    }
}
