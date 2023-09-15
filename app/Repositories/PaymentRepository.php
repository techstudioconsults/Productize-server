<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository
{
    public function update(string $key, string $value, array $array)
    {
        return Payment::where($key, $value)->update($array);
    }
}
