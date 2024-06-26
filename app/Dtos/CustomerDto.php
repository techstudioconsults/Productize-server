<?php

namespace App\Dtos;

use App\Exceptions\ServerErrorException;
use Illuminate\Support\Collection;

/**
 * @author @Intuneteq
 *
 * @version 1.0
 *
 * @since 26-06-2024
 *
 * Data Transfer Object for customer requests from paystack
 */
class CustomerDto implements IDtoFactory
{
    /**
     * CustomerDto constructor.
     *
     * @param string $id                Customer Id from payment gateway
     * @param string $email             Email From Payment Gateway
     * @param string $code              Customer Code from Payment Gateway
     * @param string $first_name        The first name saved in the payment gateway
     * @param string $last_name         The last name saved in the payment gateway
     * @param string $createdAt         Date of creation
     * @param Collection $subscriptions The Customer's subscriptions
     */
    public function __construct(
        private string $id,
        private string $email,
        private string $code,
        private string $first_name,
        private string $last_name,
        private string $createdAt,
        private Collection $subscriptions
    ) {
    }

    /**
     * Get the ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the code.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get the first name.
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->first_name;
    }

    /**
     * Get the last name.
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->last_name;
    }

    /**
     * Get the created at timestamp.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Get the subscriptions.
     *
     * @return Collection
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    /**
     * Create an instance of CustomerDto from an array of data.
     *
     * @param array $customer
     * @return self
     * @throws Exception
     */
    public static function create(array $customer): self
    {
        if (!isset($customer['id'], $customer['email'], $customer['code'], $customer['subscriptions'])) {
            throw new ServerErrorException("Invalid Customer Data Transfer");
        }

        // Create SubscriptionDto objects from the array data
        $subscriptions = collect($customer['subscriptions'])->map(function ($subscriptionData) {
            return SubscriptionDto::create([...$subscriptionData, 'code' => $subscriptionData['subscription_code']]);
        });

        return new self(
            $customer['id'],
            $customer['email'],
            $customer['code'],
            $customer['first_name'],
            $customer['last_name'],
            $customer['createdAt'],
            $subscriptions
        );
    }
}
