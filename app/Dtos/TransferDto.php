<?php

namespace App\Dtos;

use App\Exceptions\ServerErrorException;

/**
 * @author @Intuneteq
 *
 * @version 1.0
 *
 * @since 30-06-2024
 *
 * Data Transfer Object for Paystack Transfer Requests
 */
class TransferDto implements IDtoFactory
{
    /**
     * TransferDto constructor.
     *
     * @param  string  $amount  The amount of the transfer.
     * @param  string  $code  The transfer code.
     * @param  string  $created_at  The creation timestamp of the transfer.
     */
    public function __construct(
        private string $amount,
        private string $code,
        private string $created_at
    ) {
        $this->amount = (string) ((int) $this->amount / 100);
    }

    /**
     * Get the amount of the transfer.
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Get the transfer code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get the creation timestamp of the transfer.
     */
    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'transfer_code' => $this->code,
            'createdAt' => $this->created_at,
        ];
    }

    /**
     * Create an instance of TransferDto from an array of data.
     *
     * @throws ServerErrorException
     */
    public static function create(array $data): self
    {
        if (! isset($data['amount'], $data['transfer_code'], $data['createdAt'])) {
            throw new ServerErrorException('Invalid TransferDto Transfer');
        }

        return new TransferDto(
            $data['amount'],
            $data['transfer_code'],
            $data['createdAt']
        );
    }
}
