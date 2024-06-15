<?php

namespace App\Http\Controllers;

use App\Enums\PayoutStatusEnum;
use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Http\Requests\InitiateWithdrawalRequest;
use App\Http\Resources\EarningResource;
use App\Repositories\AccountRepository;
use App\Repositories\EarningRepository;
use App\Repositories\PayoutRepository;
use App\Repositories\PaystackRepository;
use Auth;
use Illuminate\Http\JsonResponse;
use Str;

class EarningController extends Controller
{
    public function __construct(
        protected EarningRepository $earningRepository,
        protected AccountRepository $accountRepository,
        protected PaystackRepository $paystackRepository,
        protected PayoutRepository $payoutRepository
    ) {
    }
    public function index()
    {
        $user = Auth::user();

        $earning = $this->earningRepository->findOne(['user_id' => $user->id]);

        return new EarningResource($earning);
    }

    public function withdraw(InitiateWithdrawalRequest $request)
    {
        // Retrieve Auth user
        $user = Auth::user();

        // Withdrawal amount
        $amount = $request->amount;

        // Initiate query for the user's payout account. Check if iit exits
        $exists = $this->accountRepository->query(['user_id' => $user->id])->exists();

        // if yes, throw error
        if (!$exists) throw new BadRequestException('You need to set up your Payout account before requesting a payout.');

        // get the first and only user's earning from the earnings table.
        $earning = $this->earningRepository->findOne(['user_id' => $user->id]);

        // Get user current balance
        $balance = $this->earningRepository->getBalance($earning);

        // Throw an error if the user attempts to withdraw more than balance.
        if ($amount > $balance) throw new BadRequestException('Insufficient balance. You cannot withdraw more than your current balance.');

        // Retrieve active payout account.
        $account = $this->accountRepository->findActive();

        // Generate the reference id.
        $reference = Str::uuid()->toString();

        try {
            //  Initiate withdrawal request by initiating a transfer from productize's paystack account.
            $response = $this->paystackRepository->initiateTransfer(
                $amount,
                $account->paystack_recipient_code,
                $reference
            );

            // Build payout entity
            $payout_entity = [
                'status' => PayoutStatusEnum::Pending->value,
                'reference' => $reference,
                'paystack_transfer_code' => $response['transfer_code'],
                'account_id' => $account->id,
                'amount' => $amount
            ];

            // Create a payout history
            $this->payoutRepository->create($payout_entity);

            // update the user's pending amount
            $this->earningRepository->update($earning, [
                'pending' => $amount
            ]);

            return new JsonResponse(['data' => 'Withdrawal Initiated']);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), 500);
        }
    }
}
