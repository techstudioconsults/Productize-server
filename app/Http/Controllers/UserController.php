<?php

namespace App\Http\Controllers;

use App\Enums\AccountEnum;
use App\Enums\PayoutStatus;
use App\Enums\Roles;
use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ServerErrorException;
use App\Exceptions\UnprocessableException;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateKycRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\PayoutRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Auth;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 *  @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 26-05-2024
 *
 * Route handler methods for User resource
 */
class UserController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository,
        protected ProductRepository $productRepository,
        protected OrderRepository $orderRepository,
        protected PayoutRepository $payoutRepository
    ) {}

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve a listing of all registed users.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $filter = [
            'role' => $request->role,
        ];

        $users = $this->userRepository->query($filter)->paginate(10);

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

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = $this->userRepository->create($data);

        event(new Registered($user));

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

                $logoUrl = config('filesystems.disks.spaces.cdn_endpoint').'/'.$path;
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

        if (! Hash::check($validated['password'], $user->password)) {
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

        $total_payouts = $this->payoutRepository->query(['status' => PayoutStatus::Completed->value])->sum('amount');

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
            'conversion_rate' => '35%',
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

    /**
     * @author @Intuneteq
     *
     * Revoke admin privileges from a user and update their role to a regular user.
     *
     * This method changes the specified user's role from admin to user by updating
     * their role in the user repository.
     *
     * @param  \App\Models\User  $user  The user whose admin privileges will be revoked.
     * @return \Illuminate\Http\Resources\Json\JsonResource Returns a JSON resource containing a success message.
     */
    public function revokeAdminRole(User $user)
    {
        $this->userRepository->guardedUpdate($user->email, 'role', Roles::USER->value);

        return new JsonResource([
            'message' => 'Admin role has been successfully revoked, and user role has been updated to regular user.',
        ]);
    }

    public function updateKyc(UpdateKycRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        $updated = $this->userRepository->update($user, $validated);

        return new UserResource($updated);
    }

    public function notifications()
    {
        $user = Auth::user();

        $notifications = $user->unreadNotifications;

        return new JsonResource($notifications);
    }

    public function readNotifications()
    {
        $user = Auth::user();

        $user->unreadNotifications->markAsRead();

        return new JsonResource(['message' => 'All notifications marked as read']);
    }
}
