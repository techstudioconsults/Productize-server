<?php

namespace App\Dtos;

use App\Enums\SubscriptionStatusEnum;
use App\Exceptions\ServerErrorException;

/**
 * @author @Intuneteq
 *
 * @version 1.0
 *
 * @since 26-06-2024
 *
 * Data Transfer Object for subscription requests from paystack
 */
class SubscriptionDto implements IDtoFactory
{
    /**
     * SubscriptionDto constructor.
     *
     * @param string $id                     Subscription Id from the Payment Gateway
     * @param string $code                   Subscription code from the Payment Gateway
     * @param int $amount
     * @param SubscriptionStatusEnum $status Subscription status
     * @param string $createdAt              Subcription Date
     */
    public function __construct(
        private string $id,
        private string $code,
        private int $amount,
        private SubscriptionStatusEnum $status,
        private string $createdAt
    ) {
    }

    /**
     * Get the subscription ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the subscription code.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get the subscription amount.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Get the subscription status.
     *
     * @return SubscriptionStatusEnum
     */
    public function getStatus(): SubscriptionStatusEnum
    {
        return $this->status;
    }

    /**
     * Get the subscription creation timestamp.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Create an instance of SubscriptionDto from an array of data.
     *
     * @param array $data
     * @return self
     * @throws ServerErrorException
     */
    public static function create(array $data): self
    {
        if (!isset($data['code'], $data['status'])) {
            throw new ServerErrorException("Invalid Subscription Data Transfer");
        }

        return new self(
            $data['id'],
            $data['code'],
            $data['amount'],
            $data['status'],
            $data['createdAt']
        );
    }
}
