<?php

namespace App\Repositories;

use App\Exceptions\UnprocessableException;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class PayoutRepository
{
    public function create(array $credentials)
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

    public function find(
        User $user,
        ?string $start_date = null,
        ?string $end_date = null,
    ) {
        $payouts = $user->payouts();

        if ($start_date && $end_date) {
            $validator = Validator::make([
                'start_date' => $start_date,
                'end_date' => $end_date
            ], [
                'start_date' => 'date',
                'end_date' => 'date'
            ]);

            if ($validator->fails()) {
                throw new UnprocessableException($validator->errors()->first());
            }

            $payouts->whereBetween('created_at', [$start_date, $end_date]);
        }

        return $payouts;
    }


    public function findByReference(string $reference)
    {
        $payout = Payout::where('reference', $reference)->first();

        return $payout;
    }
}
