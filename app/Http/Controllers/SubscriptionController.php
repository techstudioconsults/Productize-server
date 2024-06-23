<?php

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 08-06-2024
 */

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatusEnum;
use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Repositories\PaystackRepository;
use App\Repositories\SubscriptionRepository;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * Route handler methods for Subscription resource
 */
class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionRepository $subscriptionRepository,
        protected PaystackRepository $paystackRepository
    ) {}

    /**
     *  @author @Intuneteq Tobi Olanitori
     *
     * Retrieves a paginated list of all subscriptions.
     *
     * @return SubscriptionResource Returns a collection of all subscriptions.
     */
    public function index(Request $request)
    {
        $filter = [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $subscriptions = $this->subscriptionRepository->find($filter);

        return SubscriptionResource::collection($subscriptions);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Handle the creation of a new subscription for a user.
     *
     * This method checks if the user already has an active or any subscription
     * in the database or Paystack. If the user has an active subscription,
     * it returns an error. If the user has a subscription that is cancelled,
     * it suggests reactivating it. If there are no subscriptions, it creates a new one.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource The substriction initialization from paystack
     *
     * @throws \App\Exceptions\BadRequestException If the user already has an active subscription or any subscription with any status.
     */
    public function store(StoreSubscriptionRequest $request)
    {
        // check if user is subscriped on the db table
        // if yes, return error - no point continuing.
        // check if we have the user on the subscription table - First time subscriber or db wiped in dev ?
        // If we have the subscriber on the db table, return error - user is a subscriber but it's cancelled. Let them use the enable button.

        // No user on subscription table
        // fetch user on paystack
        // if user is found, check if user has any subscription history

        // If yes, it should be on the db table - Most likely a dev wipe,
        // check the status, if it is not cancelled, upgrade the user to premium - when moving to production, add the production condition to it so this behavior is not persistent in production
        // so update the table with necessary subscription info log and throw error.

        //if no user found or no subscription, create a new subscription

        // Check if the user is authenticated
        $user = $request->user('sanctum');

        // Check if the user already has an active subscription
        if ($user->isSubscribed()) {
            throw new BadRequestException("Sorry, you can't perform this action. It appears you already have an active subscription plan.");
        }

        // Check if the user has any subscription in the database
        $subscription = $this->subscriptionRepository->findOne([
            'user_id' => $user->id,
            'status' => SubscriptionStatusEnum::ACTIVE->value,
            'status' => SubscriptionStatusEnum::NON_RENEWING->value,
            'status' => SubscriptionStatusEnum::PENDING->value,
            'status' => SubscriptionStatusEnum::ATTENTION->value,
        ]);

        // If the user has a subscription, return an error with the subscription status
        if ($subscription) {
            $status = $subscription['status'];

            throw new BadRequestException(
                "Sorry, you can't perform this action. It appears you already have a subscription plan with status $status."
            );
        }

        // Data for the new subscription
        $data = [
            'user_id' => $user->id,
            'email' => $user->email,
        ];

        // Start a new subscription
        $subscription = $this->subscriptionRepository->start($data);

        return new JsonResource($subscription);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Enable a subscription for a user.
     *
     * This method checks if the subscription status is not cancelled or non-renewing.
     * If the subscription status is valid, it enables the subscription through Paystack.
     * If the subscription status is cancelled or non-renewing, it throws an error.
     *
     * @param  \App\Models\Subscription  $subscription  The subscription to be enabled.
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \App\Exceptions\BadRequestException If the subscription status is cancelled or non-renewing.
     * @throws \App\Exceptions\ApiException If an error occurs while enabling the subscription via Paystack.
     */
    public function enable(Subscription $subscription)
    {
        if (
            $subscription->status === SubscriptionStatusEnum::CANCELLED->value ||
            $subscription->status === SubscriptionStatusEnum::NON_RENEWING->value
        ) {
            throw new BadRequestException('Subscription status is cancelled');
        }

        try {
            $response = $this->paystackRepository->enableSubscription($subscription->subscription_code);

            return new JsonResponse(['data' => ['id' => $subscription->id, ...$response]]);
        } catch (\Exception $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Manage a subscription for a user.
     *
     * This method sends a request to Paystack to manage the given subscription.
     * It catches any exceptions that occur during the process and throws an ApiException.
     *
     * @param  \App\Models\Subscription  $subscription  The subscription to be managed.
     * @return \Illuminate\Http\JsonResponse Paystack dedicated page for a user to manage their subscription
     *
     * @throws \App\Exceptions\ApiException If an error occurs while managing the subscription via Paystack.
     */
    public function manage(Subscription $subscription)
    {
        try {
            $response = $this->paystackRepository->manageSubscription($subscription->subscription_code);

            return new JsonResponse(['data' => ['id' => $subscription->id, ...$response]]);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Cancel a subscription for a user.
     *
     * This method sends a request to Paystack to cancel the given subscription.
     * It checks if the subscription is already cancelled or non-renewing before proceeding.
     * It catches any exceptions that occur during the process and throws an ApiException.
     *
     * @param  \App\Models\Subscription  $subscription  The subscription to be cancelled.
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \App\Exceptions\BadRequestException If the subscription is already cancelled or non-renewing.
     * @throws \App\Exceptions\ApiException If an error occurs while cancelling the subscription via Paystack.
     */
    public function cancel(Subscription $subscription)
    {
        if (
            $subscription->status === SubscriptionStatusEnum::CANCELLED->value ||
            $subscription->status === SubscriptionStatusEnum::NON_RENEWING->value
        ) {
            throw new BadRequestException('Subscription status is cancelled');
        }

        try {
            $response = $this->paystackRepository->disableSubscription($subscription->subscription_code);

            return new JsonResponse(['data' => ['id' => $subscription->id, ...$response]]);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Get the billing details for the authenticated user.
     *
     * This method retrieves the billing information, including the current plan,
     * renewal date, and billing total for the authenticated user. If the user is
     * on a free trial, it calculates the renewal date. For subscribed users, it
     * fetches the subscription details from Paystack and prepares the response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function billing()
    {
        $user = Auth::user();

        $response = [
            'renewal_date' => null,
            'plan' => $user->account_type,
            'billing_total' => null,
            'plans' => [],
        ];

        // If on free trial
        if ($user->account_type === 'free_trial') {
            $response['renewal_date'] = Carbon::parse($user->created_at)->addDays(30);

            return new JsonResponse($response);
        }

        // User is on a free account
        if (! $user->isSubscribed()) {
            return new JsonResponse($response);
        }

        // Get subscription table.
        $db = $this->subscriptionRepository->findOne(['user_id' => $user->id]);

        // Log this issue to slack
        if (! $db) {
            return new JsonResponse($response);
        }

        $subscription_code = $db->subscription_code;

        // Log this issue to slack
        if (! $subscription_code) {
            return new JsonResponse($response);
        }

        $subscription = $this->paystackRepository->fetchSubscription($subscription_code);

        $plans = Arr::map($subscription['invoices'], function ($plan) {
            return [
                'plan' => 'premium',
                'price' => $plan['amount'] / 100,
                'status' => $plan['status'],
                'reference' => $plan['reference'],
                'date' => $plan['createdAt'],
            ];
        });

        $response = [
            'id' => $db->id,
            'renewal_date' => $subscription['next_payment_date'],
            'plan' => $user->account_type,
            'billing_total' => $subscription['amount'] / 100,
            'plans' => $plans,
        ];

        return new JsonResponse($response);
    }
}
