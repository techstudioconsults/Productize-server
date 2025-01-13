<?php

namespace App\Services\EmailMarketingProviders;

use App\Enums\EmailMarketingProvider;

class MailchimpService implements EmailMarketingServiceContract
{
    static function addSubscriber(string $email, array $fullname, string $token, EmailMarketingProvider $provider): bool
    {
        return true;
    }

    static function removeSubscriber(string $email): bool
    {
        return true;
    }

    static function getSubscribers(): array
    {
        return [];
    }
}
