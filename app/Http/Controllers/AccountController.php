<?php

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 08-06-2024
 */

namespace App\Http\Controllers;

use App\Dtos\BankDto;
use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Repositories\AccountRepository;
use App\Repositories\PaystackRepository;
use App\Repositories\UserRepository;
use Arr;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Route handler methods for Account resource
 */
class AccountController extends Controller
{
    public function __construct(
        protected AccountRepository $accountRepository,
        protected PaystackRepository $paystackRepository,
        protected UserRepository $userRepository
    ) {
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Display a listing of the user's accounts.
     *
     * This method retrieves all accounts associated with the authenticated user.
     *
     * @return AccountResource A collection of AccountResource instances.
     */
    public function index()
    {
        // Retrieve the authenticated user
        $user = Auth::user();

        // Find accounts associated with the authenticated user
        $accounts = $this->accountRepository->find([
            'user_id' => $user->id,
        ]);

        // Return a collection of AccountResource instances
        return AccountResource::collection($accounts);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Store a newly created payout account in storage.
     *
     * This method handles the creation of a new payout account for the authenticated user.
     * It validates the provided account details, checks for duplicates, and interacts with
     * the Paystack API to create a transfer recipient before storing the account in the database.
     *
     * @param  StoreAccountRequest  $request
     *                                        The request object containing the validated data for the new payout account.
     * @return \App\Http\Resources\AccountResource
     *                                             The newly created AccountResource instance.
     *
     * @throws \App\Exceptions\ConflictException
     *                                           If an account with the provided account number already exists.
     * @throws \App\Exceptions\BadRequestException
     *                                             If the provided account number is invalid.
     * @throws \App\Exceptions\ApiException
     *                                      If an error occurs while interacting with the Paystack API or while storing the account.
     */
    public function store(StoreAccountRequest $request)
    {
        // Retrieve the authenticated user
        $user = Auth::user();

        // Validate the request data
        $validated = $request->validated();

        // Extract validated data
        $account_number = $validated['account_number'];
        $bank_code = $validated['bank_code'];
        $name = $validated['name'];
        $bank_name = $validated['bank_name'];

        // Check for duplicate account
        $exists = $this->accountRepository->query([
            'account_number' => $account_number,
        ])->exists();

        if ($exists) {
            throw new ConflictException('Duplicate Account');
        }

        // Validate the account number with Paystack
        $isValidated = $this->paystackRepository->validateAccountNumber($account_number, $bank_code);

        if (!$isValidated) {
            throw new BadRequestException('Invalid Account Number');
        }

        try {
            // Create a transfer recipient with paystack
            $response = $this->paystackRepository->createTransferRecipient($name, $account_number, $bank_code);

            $account = [
                'user_id' => $user->id,
                'account_number' => $account_number,
                'paystack_recipient_code' => $response->getCode(),
                'name' => $name,
                'bank_code' => $bank_code,
                'bank_name' => $bank_name,
                'active' => 1, // Let it be the active account by default
            ];

            // Create the account in the database
            $account = $this->accountRepository->create($account);

            // Update the user's payout setup timestamp
            $this->userRepository->guardedUpdate($user->email, 'payout_setup_at', Carbon::now());

            return new AccountResource($account);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Update the specified payout account.
     *
     * This method updates the active status of an existing payout account based on the provided validated data.
     *
     * @param  \App\Models\Account  $account
     *                                        The payout account instance to be updated.
     * @param  \App\Http\Requests\UpdateAccountRequest  $request
     *                                                            The request object containing the validated data for updating the payout account.
     * @return \App\Http\Resources\AccountResource
     *                                             The updated AccountResource instance.
     */
    public function update(Account $account, UpdateAccountRequest $request)
    {
        $validated = $request->validated();

        $account->active = $validated['active'];

        $account->save();

        return new AccountResource($account);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Get a list of all banks supported by Paystack.
     *
     * This method retrieves a list of banks supported by Paystack, mapping the data to include only the bank name and code.
     *
     * @return \Illuminate\Http\JsonResponse
     *                                       The response containing the list of banks with their names and codes.
     */
    public function bankList()
    {
        // Retrieve the list of banks from the Paystack repository
        $banks = $this->paystackRepository->getBankList();

        if (!$banks) {
            return new JsonResponse([], 200);
        }

        $response = $banks->map(function (BankDto $bank) {
            return $bank->toArray();
        });

        return new JsonResponse($response, 200);
    }
}
