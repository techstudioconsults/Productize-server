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

    public function update(string $key, string $value, array $array)
    {
        return Payment::where($key, $value)->update($array);
    }
}
