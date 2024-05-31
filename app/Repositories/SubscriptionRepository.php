<?php

namespace App\Repositories;

use App\Enums\SubscriptionStatusEnum;
use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Exceptions\ServerErrorException;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SubscriptionRepository extends Repository
{
    public function __construct(
        protected PaystackRepository $paystackRepository,
        protected UserRepository $userRepository
    ) {
    }

    public function seed(): void
    {
    }

    public function start(array $entity): array
    {
        $email = $entity['email'];
        $user_id = $entity['user_id'];

        // check if customer exist on paystack
        $customer = $this->paystackRepository->fetchCustomer($email);

        // The customer has subscription
        if ($this->paystackRepository->hasSubscription($customer)) {
            $this->handleCustomerHasSubscriptionNotOnDb($customer, $user_id);
        }

        // No subscription
        $user = $this->userRepository->findById($user_id);

        try {
            if (!$customer) {
                $customer = $this->paystackRepository->createCustomer($user);
            }

            $customer_code = $customer['customer_code'];

            $subcription = $this->create([
                'customer_code' => $customer_code,
                'user_id' => $user->id,
                'status' => SubscriptionStatusEnum::PENDING->value
            ]);

            $response = $this->paystackRepository->initializeTransaction($user->email, 5000, true);

            return [
                'id' => $subcription->id,
                ...$response
            ];
        } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
        }
    }

    public function create(array $entity): Subscription
    {
        return Subscription::create($entity);
    }

    public function query(array $filter): Builder
    {
        $query = Subscription::query();

        // Apply date filter
        $this->applyDateFilters($query, $filter);

        // Apply other filters
        $query->where($filter);

        return $query;
    }

    public function find(?array $filter = []): ?Collection
    {
        return $this->query($filter ?? [])->get();
    }

    public function findById(string $id): ?Subscription
    {
        return Subscription::find($id);
    }

    public function findOne(array $filter): ?Subscription
    {
        return Subscription::where($filter)->firstOr(function () {
            return null;
        });
    }

    public function update(Model $entity, array $updates): Subscription
    {
        // Ensure that the provided entity is an instance of Order
        if (!$entity instanceof Subscription) {
            throw new ModelCastException("Subscription", get_class($entity));
        }

        // Assign the updates to the corresponding fields of the Order instance
        $entity->fill($updates);

        // Save the updated Order instance
        $entity->save();

        // Return the updated Order model
        return $entity;
    }

    /**
     * The Customer has a subcription customer account with registered with us but not in our database.
     *
     * Check if the paystack subscription is active.
     *
     * Note, this part of the code might run on your local server due to formatting the db etc but not in production!
     *
     * If the status is not cancelled hence, active for whatever reason.
     */
    private function handleCustomerHasSubscriptionNotOnDb(array $customer, string $user_id)
    {
        // First item is the current subscription plan
        $subscription = $customer['subscriptions'][0];

        $status = $subscription['status'];

        // Add customer to subscription table
        $this->create([
            'status' => $status,
            'customer_code' => $customer['customer_code'],
            'subscription_code' => $subscription['subscription_code'],
            'user_id' => $user_id
        ]);

        if ($status !== SubscriptionStatusEnum::CANCELLED->value) {
            Log::channel('slack')->alert(
                'USER HAS AN ACTIVE SUBSCRIPTION BUT IS NOT A PREMIUM USER IN DB',
                ['context' => [
                    'email' => $customer["email"],
                    'paystack_customer_code' => $customer['customer_code'],
                    'status' => $status
                ]]
            );

            $this->userRepository->guardedUpdate($customer["email"], 'account_type', 'premium');
        }

        throw new BadRequestException("Sorry, you can't perform this action. It appears you already have a subscription plan.");
    }
}
