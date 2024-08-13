<?php

namespace App\Http\Controllers;

use App\Dtos\AdminDto;
use App\Dtos\AdminUpdateDto;
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
use App\Mail\AdminDeletedMail;
use App\Mail\AdminRevokedMail;
use App\Mail\AdminUpdateMail;
use App\Mail\AdminWelcomeMail;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\PayoutRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Auth;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Mail;
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

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Super Admin create a user
     *
     * @param  StoreUserRequest  $request  The request containing the user details
     * @return UserResource The resource containing the new user details
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = $this->userRepository->create($data);

        $adminDTO = new AdminDto(
            $user->email,
            $data['password'] ?? null
        );

        event(new Registered($user));

        Mail::to($data['email'])->send(new AdminWelcomeMail($adminDTO));

        $response = ['user' => new UserResource($user), 'message' => 'Success'];

        return new JsonResponse($response, 201);
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
    public function update(UpdateUserRequest $request, $id = null)
    {
        $adminUpdate = $id !== null;
        $user = $adminUpdate ? $this->userRepository->findById($id) : Auth::user();

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

        $userUpdateDTO = new AdminUpdateDto(
            $user->full_name ?? $validated['full_name'],
            $user->email,
            $validated['password'] ?? null,
        );

        if (isset($validated['password'])) {
            $user = $this->userRepository->guardedUpdate($user->email, 'password', $validated['password']);
        }

        try {
            $user = $this->userRepository->update($user, $validated);

            // Check for profile completion
            if (! $adminUpdate) {
                $this->userRepository->profileCompletedAt($user);
            }

            // Send email notification for admin updates
            if ($adminUpdate) {
                Mail::to($user->email)->send(new AdminUpdateMail($userUpdateDTO));
            }

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

        Mail::to($user->email)->send(new AdminRevokedMail);

        return new JsonResource([
            'message' => 'Admin role has been successfully revoked, and user role has been updated to regular user.',
        ]);
    }

    /**
     * Update the KYC information of the authenticated user.
     *
     * @param  UpdateKycRequest  $request  The request containing the KYC information to be updated.
     * @return UserResource The resource representing the updated user.
     */
    public function updateKyc(UpdateKycRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        $updated = $this->userRepository->update($user, $validated);

        return new UserResource($updated);
    }

    /**
     * Get the unread notifications of the authenticated user.
     *
     * @return JsonResource A JSON resource containing the unread notifications.
     */
    public function notifications(Request $request)
    {
        $user = Auth::user();

        $type = $request->query('type'); // Get the type filter from query parameters

        $query = $user->unreadNotifications();

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->get();

        return new JsonResource($notifications);
    }

    /**
     * Mark all unread notifications of the authenticated user as read.
     *
     * @return JsonResource A JSON resource containing a success message.
     */
    public function readNotifications(Request $request)
    {
        $user = Auth::user();

        $type = $request->input('type'); // Get the type filter from query parameters

        // Fetch unread notifications, optionally filter by type type
        $query = $user->unreadNotifications();

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->get();

        // Mark the filtered notifications as read
        $notifications->markAsRead();

        return new JsonResource(['message' => 'Notifications marked as read']);
    }

    public function deleteAdmin($id)
    {
        try {
            $user = $this->userRepository->findById($id);

            if (! $user) {
                return new JsonResource([
                    'message' => 'User not found',
                ]);
            }

            $this->userRepository->deleteOne($user);

            Mail::to($user->email)->send(new AdminDeletedMail);

            return new JsonResource([
                'message' => 'Admin account has been deleted',
            ]);
        } catch (\Throwable $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        }
    }

    public function downloadAdmin()
    {
        $filter = [
            'role' => 'ADMIN',
        ];

        $admin_users = $this->userRepository->query($filter)->get();

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');
        $fileName = "admin_users_$now.csv";

        $columns = ['User Name', 'User Email', 'Date'];
        $data = [$columns];

        foreach ($admin_users as $user) {
            $data[] = [
                $user->full_name,
                $user->email,
                $user->updated_at,
            ];
        }

        $filePath = $this->generateCsv($fileName, $data);

        return $this->streamFile($filePath, $fileName, 'text/csv');
    }
}
