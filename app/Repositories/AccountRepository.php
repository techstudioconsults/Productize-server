<?php

namespace App\Repositories;

use App\Exceptions\ModelCastException;
use App\Exceptions\ServerErrorException;
use App\Http\Requests\StoreAccountRequest;
use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AccountRepository extends Repository
{
    public function seed(): void {}

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

    public function query(array $filter): Builder
    {
        $query = Account::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    public function find(?array $filter): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    public function findById(string $id): ?Account
    {
        return Account::find($id);
    }

    public function findOne(array $filter): ?Account
    {
        return Account::where($filter)->firstOr(function () {
            return null;
        });
    }

    public function findActive(): Account
    {
        return Account::where(['active' => true])->firstOr(function () {
            return null;
        });
    }

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
