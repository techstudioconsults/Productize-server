<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Models\Payout;
use App\Models\PayOutAccount;
use App\Models\Subaccounts;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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

    public function updateWithdraws(string $user_id, int $amount)
    {
        $payment = Payment::firstWhere('user_id', $user_id);

        $payment->withdrawn_earnings = $payment->withdrawn_earnings + $amount;

        $payment->save();
    }

    public function createPayout(array $credentials)
    {
        $payout = new Payout();

        $payout->pay_out_account_id = $credentials['pay_out_account_id'];
        $payout->reference = $credentials['reference'];
        $payout->status = $credentials['status'];
        $payout->paystack_transfer_code = $credentials['paystack_transfer_code'];
        $payout->amount = $credentials['amount'];

        $payout->save();

        return $payout;
    }

    public function getPayoutByReference(string $reference)
    {
        $payout = Payout::where('reference', $reference)->first();

        return $payout;
    }
}
