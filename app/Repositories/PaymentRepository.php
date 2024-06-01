<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Models\PayOutAccount;
use App\Models\User;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @deprecated Too ambigous and does not follow SOLID principle.
 * I am breaking it down into Account and Subscription resource
 */
class PaymentRepository
{
    private $commission = 0.05;

    public function getCommission()
    {
        return $this->commission;
    }

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

    public function createPayOutAccount(array $credentials)
    {
        return PayOutAccount::create($credentials);
    }

    public function updatePayOutAccount(string $key, string $value, array $updatables)
    {
        return PayOutAccount::where($key, $value)->update($updatables);
    }

    public function updateEarnings(string $user_id, int $amount)
    {
        $payment = Payment::firstOrCreate([
            'user_id' => $user_id
        ]);

        $payment->total_earnings = $payment->total_earnings + $amount;

        $payment->save();
    }

    public function updateWithdraws(string $user_id, int $amount): void
    {
        $payment = Payment::firstWhere('user_id', $user_id);

        $payment->withdrawn_earnings = $payment->withdrawn_earnings + $amount;

        $payment->save();
    }
}
