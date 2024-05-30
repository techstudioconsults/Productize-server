<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Http\Requests\StorePayOutRequest;
use App\Http\Requests\UpdatePayOutRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Repositories\AccountRepository;
use App\Repositories\PaystackRepository;
use App\Repositories\UserRepository;
use Auth;
use Carbon\Carbon;

class AccountController extends Controller
{
    public function __construct(
        protected AccountRepository $accountRepository,
        protected PaystackRepository $paystackRepository,
        protected UserRepository $userRepository
    ) {
    }

    public function index()
    {
        $user = Auth::user();

        $accounts = $this->accountRepository->find([
            'user_id' => $user->id
        ]);

        return AccountResource::collection($accounts);
    }

    public function store(StorePayOutRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        $account_number = $validated['account_number'];

        $bank_code = $validated['bank_code'];

        $name = $validated['name'];

        $bank_name = $validated['bank_name'];

        $exists = $this->accountRepository->query([
            'account_number' => $account_number
        ])->exists();

        if ($exists) {
            throw new ConflictException('Duplicate Account');
        }

        $isValidated = $this->paystackRepository->validateAccountNumber($account_number, $bank_code);

        if (!$isValidated) throw new BadRequestException('Invalid Account Number');

        try {
            // Create a transfer recipient with paystack
            $response = $this->paystackRepository->createTransferRecipient($name, $account_number, $bank_code);

            $account = [
                'user_id' => $user->id,
                'account_number' => $account_number,
                'paystack_recipient_code' => $response['recipient_code'],
                'name' => $name,
                'bank_code' => $bank_code,
                'bank_name' => $bank_name,
                'active' => 1 // Let it be the active account by default
            ];

            $account = $this->accountRepository->create($account);

            $this->userRepository->guardedUpdate($user->email, 'payout_setup_at', Carbon::now());

            return new AccountResource($account);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    public function update(Account $account, UpdatePayOutRequest $request)
    {
        $validated = $request->validated();

        $account->active = $validated['active'];

        $account->save();

        return new AccountResource($account);
    }
}
