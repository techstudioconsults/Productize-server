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
 * Data Transfer Object for Transfer Recipient requests from paystack
 */
class TransferRecipientDto implements IDtoFactory
{
    /**
     * TransferRecipientDto constructor.
     *
     * @param string $code The recipient code.
     * @param string $name The recipient name.
     * @param string $created_at The creation date.
     */
    public function __construct(
        private string $code,
        private string $name,
        private string $created_at
    ) {
    }

    /**
     * Get the recipient code.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get the recipient name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the creation date.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'recipient_code' => $this->code,
            'name' => $this->name,
            'createdAt' => $this->created_at,
        ];
    }

    /**
     * Create a new instance of TransferRecipientDto from array data.
     *
     * @param array $data The array containing transfer recipient data.
     * @return self
     * @throws ServerErrorException If required fields are missing in $data.
     */
    public static function create(array $data): self
    {
        if (!isset($data['recipient_code'], $data['name'], $data['createdAt'])) {
            throw new ServerErrorException("Invalid Transfer recipient Transfer");
        }

        return new self($data['recipient_code'], $data['name'], $data['createdAt']);
    }
}
