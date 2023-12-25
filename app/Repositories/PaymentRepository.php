<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Models\Subaccounts;
use App\Models\User;

class PaymentRepository
{
    public function create(array $credentials, User $user)
    {
        $data = array_merge($credentials, ['user_id' => $user->id]);
        $payment =  Payment::create($data);

        return $payment;
    }

    /**
     * @param key filter column.
     * @param value filter column value.
     * @param updatables key pair values to be updated.
     * @return Payment
     */
    public function update(string $key, string $value, array $updatables)
    {
        return Payment::where($key, $value)->update($updatables);
    }

    public function updateOrCreate(string $user_id, array $updatables)
    {
        return Payment::updateOrCreate(["user_id" => $user_id], $updatables);
    }

    public function createSubAccount(array $credentials)
    {
        return Subaccounts::create($credentials);
    }

    public function updateSubaccount(string $key, string $value, array $updatables)
    {
        return Subaccounts::where($key, $value)->update($updatables);
    }
}
