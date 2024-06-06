<?php

/**
 *  @author @Intuneteq Tobi Olanitori
 * @version 1.0
 * @since 26-05-2024
 */

namespace App\Http\Controllers;

// use App\Events\OrderCreated;

use App\Events\OrderCreated as EventsOrderCreated;
use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ServerErrorException;
use App\Exceptions\UnprocessableException;
use App\Http\Requests\RequestHelpRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Mail\RequestHelp;
use App\Notifications\OrderCreated;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use App\Repositories\UserRepository;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

/**
 * Route handler methods for User resource
 */
class UserController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository,
        protected ProductRepository $productRepository
    ) {
    }

    /**
     *  @author @Intuneteq Tobi Olanitori
     *
     * Display the authenticated user's details.
     *
     * This method returns a UserResource instance containing the details of the authenticated user.
     *
     * @param Request $request The HTTP request instance.
     * @return UserResource The resource instance containing the user's details.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // EventsOrderCreated::dispatch($user);
        $user->notify(new OrderCreated());

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
     * @param App\Http\Requests\UpdateUserRequest $request The request containing the updated user data.
     *
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
                $path  = Storage::putFileAs('avatars', $logo, $originalName);

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
     * @param Illuminate\Http\Request $request The request containing the password change data.
     * @return \App\Http\Resources\UserResource The resource instance containing the updated user details.
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
                Password::min(8)->mixedCase()->numbers()->symbols()
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
     * Send a request for help.
     *
     * This method sends an email to the designated help email address with the subject and message provided
     * in the request. If the request includes an email address, it will use the authenticated user's email
     * address by default unless otherwise specified.
     *
     * @param \App\Http\Requests\RequestHelpRequest $request The validated request containing the subject and message.
     * @return JsonResponse
     */
    public function requestHelp(RequestHelpRequest $request)
    {
        $validated = $request->validated();

        $email = Auth::user()->email;

        if ($request->exists('email')) {
            $validated['email'] = $email;
        }

        Mail::to(['tsa.projecttesting@gmail.com'])->send(
            new RequestHelp(
                $email,
                $validated['subject'],
                $validated['message']
            )
        );

        return new JsonResponse(
            ['message' => 'email sent']
        );
    }
}
