<?php

/**
 *  @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 26-05-2024
 */

namespace App\Http\Controllers;

use App\Enums\AccountEnum;
use App\Enums\PayoutStatusEnum;
use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ServerErrorException;
use App\Exceptions\UnprocessableException;
use App\Helpers\Services\HasFileGenerator;
use App\Http\Requests\RequestHelpRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Mail\RequestHelp;
use App\Repositories\OrderRepository;
use App\Repositories\PayoutRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 * Route handler methods for User resource
 */
class UserController extends Controller
{
    use HasFileGenerator;

    public function __construct(
        protected UserRepository $userRepository,
        protected ProductRepository $productRepository,
        protected OrderRepository $orderRepository,
        protected PayoutRepository $payoutRepository
    ) {
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve a listing of all registed users.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $users = $this->userRepository->query([])->paginate(10);

        return UserResource::collection($users);
    }

    /**
     *  @author @Intuneteq Tobi Olanitori
     *
     * Display the authenticated user's details.
     *
     * This method returns a UserResource instance containing the details of the authenticated user.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return UserResource The resource instance containing the user's details.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return new UserResource($user);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     * Update the authenticated user's profile.
     *
     * This method updates the authenticated user's profile using the validated data from the request.
     * If the request includes a new logo image, it is uploaded and the URL is stored in the user's profile.
     * After updating the user's profile, it checks for profile completion and updates the 'profile_completed_at'
     * field if all required profile properties are filled.
     *
     * @param  App\Http\Requests\UpdateUserRequest  $request  The request containing the updated user data.
     * @return \App\Http\Resources\UserResource The resource instance containing the updated user details.
     *
     * @throws \App\Exceptions\ServerErrorException If an error occurs while uploading the logo.
     * @throws \App\Exceptions\ApiException If an error occurs during the update process.
     */
    public function update(UpdateUserRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        if (isset($validated['logo'])) {
            $logo = $validated['logo'];

            unset($validated['logo']);

            $originalName = $logo->getClientOriginalName();

            $logoUrl = null;

            try {
                $path = Storage::putFileAs('avatars', $logo, $originalName);

                $logoUrl = config('filesystems.disks.spaces.cdn_endpoint') . '/' . $path;
            } catch (\Throwable $th) {
                throw new ServerErrorException($th->getMessage());
            }

            $validated['logo'] = $logoUrl;
        }

        try {
            $user = $this->userRepository->update($user, $validated);

            // Check for profile completion
            $this->userRepository->profileCompletedAt($user);

            return new UserResource($user);
        } catch (Throwable $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Change the authenticated user's password.
     *
     * This method validates the request data to ensure it meets the password requirements.
     * It then verifies the current password provided in the request against the authenticated user's password.
     * If the verification is successful, it updates the user's password with the new password provided in the request.
     *
     * @param  Illuminate\Http\Request  $request  The request containing the password change data.
     * @return \App\Http\Resources\UserResource The resource instance containing the updated user details.
     *
     * @throws \App\Exceptions\UnprocessableException If the request data fails validation.
     * @throws \App\Exceptions\BadRequestException If the current password provided is incorrect.
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'new_password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            throw new UnprocessableException($validator->errors()->first());
        }

        $validated = $validator->validated();

        $user = Auth::user();

        if (!Hash::check($validated['password'], $user->password)) {
            throw new BadRequestException('Incorrect Password');
        }

        $user = $this->userRepository->guardedUpdate($user->email, 'password', $validated['new_password']);

        return new UserResource($user);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve statistical data for products, sales, payouts, and users.
     *
     * This method calculates and returns the total number of products,
     * total sales quantity, total completed payouts amount, and total number
     * of users in the system. The data is returned as a JSON resource.
     *
     * @return JsonResource A JSON resource containing the statistical data.
     */
    public function stats()
    {
        $total_products = $this->productRepository->query([])->count();

        $total_sales = $this->orderRepository->query([])->sum('quantity');

        $total_payouts = $this->payoutRepository->query(['status' => PayoutStatusEnum::Completed->value])->sum('amount');

        $total_users = $this->userRepository->query([])->count();

        $total_subscribed_users = $this->userRepository->query(['account_type' => AccountEnum::Premium->value])->count();

        $total_trial_users = $this->userRepository->query(['account_type' => AccountEnum::Free_Trial->value])->count();

        return new JsonResource([
            'total_products' => $total_products,
            'total_sales' => $total_sales,
            'total_payouts' => $total_payouts,
            'total_users' => $total_users,
            'total_subscribed_users' => $total_subscribed_users,
            'total_trial_users' => $total_trial_users,
            'conversion_rate' => '35%'
        ]);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Generate and download a CSV file containing user information.
     *
     * Retrieves all users from the repository, formats their data into CSV format,
     * and initiates a file download for the generated CSV file.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse The streamed response containing the CSV file.
     */
    public function download()
    {
        $users = $this->userRepository->find();

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');
        $fileName = "users_$now.csv";

        $columns = ['Name', 'Email', 'Status', 'Last Activity Date'];
        $data = [$columns];

        foreach ($users as $user) {
            $data[] = [
                $user->full_name,
                $user->email,
                $user->status,
                $user->updated_at,
            ];
        }

        $filePath = $this->generateCsv($fileName, $data);

        return $this->streamFile($filePath, $fileName, 'text/csv');
    }
}
