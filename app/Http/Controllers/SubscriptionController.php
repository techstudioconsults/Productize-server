<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\BadRequestException;
use App\Models\Subscription;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Repositories\PaystackRepository;
use App\Repositories\SubscriptionRepository;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionRepository $subscriptionRepository,
        protected PaystackRepository $paystackRepository
    ) {
    }

    /**
     * Store a newly created resource in storage.
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

        $user = $request->user('sanctum');

        // Is the user subscribed ?
        if ($user->isSubscribed()) {
            throw new BadRequestException("Sorry, you can't perform this action. It appears you already have an active subscription plan.");
        }

        $subscription = $this->subscriptionRepository->findOne(['user_id' => $user->id]);

        // The user has a subscription plan
        if ($subscription) {
            $status = $subscription['status'];
            throw new BadRequestException(
                "Sorry, you can't perform this action. It appears you already have a subscription plan with status $status."
            );
        }

        $data = [
            'id' => $user->id,
            'email' => $user->email,
        ];

        $subscription = $this->subscriptionRepository->start($data);

        return new JsonResource($subscription);
    }

    public function enable(Subscription $subscription)
    {
        try {
            $response = $this->paystackRepository->enableSubscription($subscription->subscription_code);
            return new JsonResponse(['data' => ['id' => $subscription->id, ...$response]]);
        } catch (\Exception $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    public function manage(Subscription $subscription)
    {
        try {
            $response = $this->paystackRepository->manageSubscription($subscription->subscription_code);

            return new JsonResponse(['data' => ['id' => $subscription->id, ...$response]]);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    public function cancel(Subscription $subscription)
    {
        try {
            $response = $this->paystackRepository->disableSubscription($subscription->subscription_code);

            return new JsonResponse(['data' => ['id' => $subscription->id, ...$response]]);
        } catch (\Throwable $th) {
            throw new ApiException($th->getMessage(), $th->getCode());
        }
    }

    public function billing()
    {
        $user = Auth::user();

        $response = [
            'renewal_date' => null,
            'plan' => $user->account_type,
            'billing_total' => null,
            'plans' => []
        ];

        if ($user->account_type === 'free_trial') {
            $response['renewal_date'] = Carbon::parse($user->created_at)->addDays(30);
            return new JsonResponse($response);
        }

        if ($user->isSubscribed()) {

            $subscription_id = $this->subscriptionRepository->findOne(['user_id' => $user->id])->subscription_code;

            if ($subscription_id) {
                $subscription = $this->paystackRepository->fetchSubscription($subscription_id);

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
                    'renewal_date' => $subscription['next_payment_date'],
                    'plan' => $user->account_type,
                    'billing_total' => $subscription['amount'] / 100,
                    'plans' => $plans
                ];
            }
        }

        return new JsonResponse($response);
    }
}
