<?php

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Exceptions\ServerErrorException;
use App\Http\Requests\StoreAccountRequest;
use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 30-05-2024
 *
 * Repository for Account resource
 */
class AccountRepository extends Repository
{
    /**
     * @author @Intuneteq
     *
     * Create a new Account entity based on the provided data.
     *
     * @param  array  $entity  The associative array containing data to create the Account.
     * @return Account The created Account entity.
     *
     * @throws ServerErrorException If validation fails based on specified rules.
     */
    public function create(array $entity): Account
    {
        $rules = (new StoreAccountRequest())->rules();

        // Add the 'user_id' rule to the validation rules
        $rules['user_id'] = 'required';

        if (! $this->isValidated($entity, $rules)) {
            throw new ServerErrorException($this->getValidator()->errors()->first());
        }

        return Account::create($entity);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Query accounts based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Builder The query builder for accounts.
     */
    public function query(array $filter): Builder
    {
        $query = Account::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find accounts based on the provided filter.
     *
     * @param  array|null  $filter  The filter criteria to apply (optional).
     * @return Collection The collection of found accounts.
     */
    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a account by its ID.
     *
     * @param  string  $id  The ID of the account to find.
     * @return Account|null The found account instance, or null if not found.
     */
    public function findById(string $id): ?Account
    {
        return Account::find($id);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Find a single account based on the provided filter.
     *
     * @param  array  $filter  The filter criteria to apply.
     * @return Account|null The found cart instance, or null if not found.
     */
    public function findOne(array $filter): ?Account
    {
        return Account::where($filter)->firstOr(function () {
            return null;
        });
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve the first active Account.
     *
     * @return Account|null The first active Account instance, or null if none found.
     */
    public function findActive(): ?Account
    {
        return $this->findOne(['active' => true]);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Update an existing Account entity with the provided updates.
     *
     * @param  Model  $entity  The Account entity to update.
     * @param  array  $updates  The associative array containing fields to update on the Account entity.
     * @return Account The updated Account entity.
     *
     * @throws ModelCastException If the provided entity is not an instance of Account.
     */
    public function update(Model $entity, array $updates): Account
    {
        // Ensure that the provided entity is an instance of Order
        if (! $entity instanceof Account) {
            throw new ModelCastException('Account', get_class($entity));
        }

        // Assign the updates to the corresponding fields of the Account instance
        $entity->fill($updates);

        // Save the updated Account instance
        $entity->save();

        // Return the updated Account model
        return $entity;
    }
}
