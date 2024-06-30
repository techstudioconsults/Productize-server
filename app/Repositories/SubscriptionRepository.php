<?php

namespace App\Repositories;

use App\Dtos\CustomerDto;
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
    // Productize's subscription price
    public const PRICE = '5000';

    public function __construct(
        protected PaystackRepository $paystackRepository,
        protected UserRepository $userRepository
    ) {
    }

    /**
     * @author @Intuneteq
     *
     * Start the subscription process for a user.
     *
     * @param array $entity The entity containing user information.
     * @return array The response containing subscription and transaction details.
     *
     * @throws ServerErrorException If any error occurs during the process.
     */
    public function start(array $entity): array
    {
        $email = $entity['email'];
        $userId = $entity['user_id'];

        try {
            // Attempt to retrieve the customer from paystack
            $customer = $this->paystackRepository->fetchCustomer($email);

            // Check if the customer is registered to paystack and has a currently running subscription
            if ($customer && $customer->isSubscribed()) {
                // Handle cases where the customer has a subscription but it's not in the database
                $this->handleCustomerHasSubscriptionNotOnDb($customer, $userId);
            } else {
                // Fetch user details from the local database
                $user = $this->userRepository->findById($userId);

                // If the customer does not exist, create a new customer on Paystack
                if (!$customer) {
                    $customer = $this->paystackRepository->createCustomer($user);
                }

                $customer_code = $customer->getCode();

                // Create a new subscription in the local database
                $subscription = $this->create([
                    'customer_code' => $customer_code,
                    'user_id' => $user->id,
                    'status' => SubscriptionStatusEnum::PENDING->value,
                ]);

                // Initialize the transaction with Paystack
                $response = $this->paystackRepository->initializeTransaction($user->email, 5000, true);

                return ['id' => $subscription->id, ...$response->toArray()];
            }
        } catch (\Throwable $th) {
            throw new ServerErrorException($th->getMessage());
        }

        // Default response if no conditions are met
        return [
            'message' => 'No action required',
        ];
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
            throw new ModelCastException('Subscription', get_class($entity));
        }

        // Assign the updates to the corresponding fields of the Order instance
        $entity->fill($updates);

        // Save the updated Order instance
        $entity->save();

        // Return the updated Order model
        return $entity;
    }

    /**
     * Handle the case where a customer has a subscription but it's not in the local database.
     * This Keeps database in sync with paystack.
     *
     * The Customer has a subcription customer account with registered with us but not in our database.
     *
     * Check if the paystack subscription is active.
     *
     * Note, this part of the code might run on your local server due to formatting the db etc but not in production!
     *
     * If the status is not cancelled hence, active for whatever reason.
     *
     * * @param CustomerDto $customer The customer data transfer object.
     * @param int $userId The user ID.
     */
    private function handleCustomerHasSubscriptionNotOnDb(CustomerDto $customer, string $user_id)
    {
        // First item is the current subscription plan
        $subscription = $customer->getSubscriptions()->first();

        $status = $subscription->getStatus();

        // Add customer to subscription table
        $this->create([
            'status' => $status->value,
            'customer_code' => $customer->getCode(),
            'subscription_code' => $subscription->getCode(),
            'user_id' => $user_id,
        ]);

        // If the user subscription status is not cancelled or pending, upgrade the user to premium
        if ($status->value !== SubscriptionStatusEnum::CANCELLED->value && $status !== SubscriptionStatusEnum::PENDING->value) {
            Log::channel('slack')->alert(
                'USER HAS AN ACTIVE SUBSCRIPTION BUT IS NOT A PREMIUM USER IN DB',
                ['context' => [
                    'email' => $customer->getEmail(),
                    'paystack_customer_code' => $customer->getCode(),
                    'status' => $status,
                ]]
            );

            $this->userRepository->guardedUpdate($customer->getEmail(), 'account_type', 'premium');
        }

        // Then return an error.
        throw new BadRequestException("Sorry, you can't perform this action. It appears you already have a subscription plan.");
    }
}
