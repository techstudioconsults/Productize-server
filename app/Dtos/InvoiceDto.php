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
     * @param  int  $amount  Amount charged
     * @param  string  $status  Current Charge Status
     * @param  string  $reference  Invoice reference id
     * @param  string  $createdAt  Date of creation
     */
    public function __construct(
        private int $amount,
        private string $status,
        private string $reference,
        private string $description,
        private string $createdAt,
    ) {}

    /**
     * Get the invoice amount.
     */
    public function getAmount(): int
    {
        return $this->amount / 100; // convert to naira
    }

    /**
     * Get the invoice status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the invoice reference.
     */
    public function getReference(): string
    {
        return $this->reference;
    }

    /**
     * Get the invoice reference.
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Get the invoice creation timestamp.
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Get formatted properties.
     */
    public function toArray(): array
    {
        return [
            'plan' => 'premium',
            'price' => $this->getAmount(),
            'status' => $this->getStatus(),
            'reference' => $this->getReference(),
            'description' => $this->getDescription(),
            'date' => $this->getCreatedAt(),
        ];
    }

    /**
     * Create an instance of InvoiceDto from an array of data.
     *
     * @throws ServerErrorException
     */
    public static function create(array $data): self
    {
        if (! isset($data['amount'], $data['status'], $data['createdAt'])) {
            throw new ServerErrorException('Invalid Invoice Data Transfer');
        }

        return new self(
            $data['amount'],
            $data['status'],
            $data['reference'] ?? '',
            $data['description'] ?? '',
            $data['createdAt'],
        );
    }
}
