<?php

namespace App\Repositories;

use App\Models\Payment;
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
}
