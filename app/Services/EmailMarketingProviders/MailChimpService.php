<?php

namespace App\Services\EmailMarketingProviders;

class MailchimpService implements EmailMarketingServiceContract
{
    public function addSubscriber(array $data): bool
    {
        return true;
    }

    public function removeSubscriber(string $email): bool
    {
        return true;
    }

    public function getSubscribers(): array
    {
        return [];
    }
}
