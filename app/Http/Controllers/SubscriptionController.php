<?php

namespace App\Http\Controllers;

use App\Exceptions\BadRequestException;
use App\Models\Subscription;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Repositories\SubscriptionRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionRepository $subscriptionRepository
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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

        $user = $request->user();

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

    /**
     * Display the specified resource.
     */
    public function show(Subscription $subscription)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription)
    {
        //
    }
}
