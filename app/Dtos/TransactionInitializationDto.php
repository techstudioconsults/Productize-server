<?php

namespace App\Dtos;

use App\Exceptions\ServerErrorException;

class TransactionInitializationDto implements IDtoFactory
{
    /**
     * @param string $authorization_url
     * @param string $access_code
     * @param string $reference
     */
    public function __construct(
        private string $authorization_url,
        private string $access_code,
        private string $reference,

    ) {
    }

    /**
     * Get the authorization URL.
     *
     * @return string
     */
    public function getAuthorizationUrl(): string
    {
        return $this->authorization_url;
    }

    /**
     * Get the access code.
     *
     * @return string
     */
    public function getAccessCode(): string
    {
        return $this->access_code;
    }

    /**
     * Get the reference.
     *
     * @return string
     */
    public function getReference(): string
    {
        return $this->reference;
    }

    /**
     * Retrieve formatted Result
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'authorization_url' => $this->getAuthorizationUrl(),
            'access_code' => $this->getAccessCode(),
            'reference' => $this->getReference()
        ];
    }

    /**
     * Create a new instance of TransactionInitializationDto from array data.
     *
     * @param array $data The array containing initialization data.
     *
     * @return self
     *
     * @throws ServerErrorException If required fields are missing in $data.
     */
    public static function create(array $data): self
    {
        if (!isset($data['authorization_url'], $data['access_code'], $data['reference'])) {
            throw new ServerErrorException("Invalid TransactionIntialization Data Transfer");
        }
        return new self(
            $data['authorization_url'],
            $data['access_code'],
            $data['reference']
        );
    }
}
