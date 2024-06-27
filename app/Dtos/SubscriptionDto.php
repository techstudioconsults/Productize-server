<?php

namespace App\Dtos;

use App\Enums\SubscriptionStatusEnum;
use App\Exceptions\ServerErrorException;
use Illuminate\Support\Collection;

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
     * @param string $next_payment_date      Date of Next Payment
     * @param string $createdAt              Subcription Date
     * @var Collection<int, InvoiceDto>      A collection of all the subscription invoices
     */
    public function __construct(
        private string $id,
        private string $code,
        private int $amount,
        private SubscriptionStatusEnum $status,
        private string $next_payment_date,
        private string $createdAt,
        private Collection $invoices
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
        return $this->amount / 100; // convert to naira
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
     * Get the subscription next payment date timestamp.
     *
     * @return string
     */
    public function getNextPaymentDate(): string
    {
        return $this->next_payment_date;
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
     * Get the subscription invoices.
     *
     * @return Collection
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    /**
     * Get the subscription invoices formatted response.
     *
     * @return array
     */
    public function getPlans()
    {
        return $this->invoices->map(function (InvoiceDto $invoice) {
            return [
                'plan' => 'premium',
                'price' => $invoice->getAmount(),
                'status' => $invoice->getStatus(),
                'reference' => $invoice->getReference(),
                'date' => $invoice->getCreatedAt(),
            ];
        });
    }

    public function getTotalBilling(): int
    {
        return $this->invoices->sum(fn(InvoiceDto $invoice) => $invoice->getAmount());
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
        if (!isset($data['subscription_code'], $data['status'], $data['next_payment_date'], $data['invoices'])) {
            throw new ServerErrorException("Invalid Subscription Data Transfer");
        }

        // Create SubscriptionDto objects from the array data
        $invoices = collect($data['invoices'])->map(function ($invoice) {
            return InvoiceDto::create($invoice);
        });

        $status = SubscriptionStatusEnum::from($data['status']);

        return new self(
            $data['id'],
            $data['subscription_code'],
            $data['amount'],
            $status,
            $data['next_payment_date'],
            $data['createdAt'],
            $invoices
        );
    }
}
