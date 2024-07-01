<?php

namespace App\Dtos;

use App\Exceptions\ServerErrorException;

/**
 * @author @Intuneteq
 *
 * @version 1.0
 *
 * @since 29-06-2024
 *
 * Data Transfer Object for bank list requests from paystack
 */
class BankDto implements IDtoFactory
{
    /**
     * BankDto constructor.
     *
     * @param  string  $name  Bank Name
     * @param  string  $code  Bank Code
     */
    public function __construct(private string $name, private string $code) {}

    /**
     * Get the name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get formatted properties.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'code' => $this->getCode(),
        ];
    }

    /**
     * Create an instance of BankDto from an array of data.
     *
     * @param  array  $data  Response data from paystack
     *
     * @throws ServerErrorException When the bank name and code are not in the array.
     */
    public static function create(array $data): self
    {
        if (! isset($data['name'], $data['code'])) {
            throw new ServerErrorException('Invalid Bank Data Transfer');
        }

        return new BankDto($data['name'], $data['code']);
    }
}
