<?php

namespace App\Dtos;

use App\Exceptions\ServerErrorException;

/**
 * @author @Intuneteq
 *
 * @version 1.0
 *
 * @since 26-06-2024
 *
 * Data Transfer Object for invoice requests from paystack
 */
class InvoiceDto implements IDtoFactory
{
    /**
     * InvoiceDto constructor.
     *
     * @param int $amount           Amount charged
     * @param string $status        Current Charge Status
     * @param string $reference     Invoice reference id
     * @param string $createdAt     Date of creation
     */
    public function __construct(
        private int $amount,
        private string $status,
        private string $reference,
        private string $createdAt,
    ) {
    }

    /**
     * Get the invoice amount.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount / 100; // convert to naira
    }

    /**
     * Get the invoice status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the invoice reference.
     *
     * @return string
     */
    public function getReference(): string
    {
        return $this->reference;
    }

    /**
     * Get the invoice creation timestamp.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Create an instance of InvoiceDto from an array of data.
     *
     * @param array $data
     * @return self
     * @throws ServerErrorException
     */
    public static function create(array $data): self
    {
        if (!isset($data['amount'], $data['status'], $data['reference'], $data['createdAt'])) {
            throw new ServerErrorException("Invalid Invoice Data Transfer");
        }

        return new self(
            $data['amount'],
            $data['status'],
            $data['reference'],
            $data['createdAt'],
        );
    }
}
