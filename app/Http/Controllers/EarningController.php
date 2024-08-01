<?php

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 09-06-2024
 */

namespace App\Http\Controllers;

use App\Enums\PayoutStatus;
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
use Illuminate\Http\Resources\Json\JsonResource;
use Str;

/**
 * Route handler methods for Earning resource
 */
class EarningController extends Controller
{
    public function __construct(
        protected EarningRepository $earningRepository,
        protected AccountRepository $accountRepository,
        protected PaystackRepository $paystackRepository,
        protected PayoutRepository $payoutRepository
    ) {
    }


    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Display the total earnings statistics
     *
     * @return JsonResource
     */

    public function index()
    {
        $total_earnings = $this->earningRepository->query([])->sum('total_earnings');

        $withdrawn_earnings = $this->earningRepository->query([])->sum('withdrawn_earnings');

        $available_earnings = $total_earnings - $withdrawn_earnings;


        return new JsonResource([
            'total_earnings' => $total_earnings,
            'withdrawn_earnings' => $withdrawn_earnings,
            'available_earnings' => $available_earnings
        ]);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Display the authenticated user's earnings.
     *
     * @return EarningResource
     */
    public function user()
    {
        $user = Auth::user();

        $earning = $this->earningRepository->findOne(['user_id' => $user->id]);

        if (!$earning) {
            return new JsonResource([
                'id' => '',
                'user_id' => $user->id,
                'total_earnings' => '',
                'withdrawn_earnings' => '',
                'available_earnings' => '',
                'pending' => '',
            ]);
        }

        return new EarningResource($earning);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Initiate a withdrawal for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \App\Exceptions\BadRequestException
     * @throws \App\Exceptions\ApiException
     */
    public function withdraw(InitiateWithdrawalRequest $request)
    {
        // Retrieve Auth user
        $user = Auth::user();

        // Withdrawal amount
        $amount = $request->amount;

        // Initiate query for the user's payout account. Check if iit exits
        $exists = $this->accountRepository->query(['user_id' => $user->id])->exists();

        // if yes, throw error
        if (!$exists) {
            throw new BadRequestException('You need to set up your Payout account before requesting a payout.');
        }

        // get the first and only user's earning from the earnings table.
        $earning = $this->earningRepository->findOne(['user_id' => $user->id]);

        // Get user current balance
        $balance = $this->earningRepository->getBalance($earning);

        // Throw an error if the user attempts to withdraw more than balance.
        if ($amount > $balance) {
            throw new BadRequestException('Insufficient balance. You cannot withdraw more than your current balance.');
        }

        // Retrieve active payout account.
        $account = $this->accountRepository->findActive(['user_id' => $user->id]);

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
                'status' => PayoutStatus::Pending->value,
                'reference' => $reference,
                'paystack_transfer_code' => $response->getCode(),
                'account_id' => $account->id,
                'amount' => $amount,
            ];

            // Create a payout history
            $this->payoutRepository->create($payout_entity);

            // update the user's pending amount
            $this->earningRepository->update($earning, [
                'pending' => $amount,
            ]);

            return new JsonResponse(['data' => 'Withdrawal Initiated']);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), 500);
        }
    }
}
