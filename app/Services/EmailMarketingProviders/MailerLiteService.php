<?php

namespace App\Services\EmailMarketingProviders;

use App\Enums\EmailMarketingProvider;

class MailerLiteService implements EmailMarketingServiceContract
{
    static function addSubscriber(string $email, string $fullname, string $token, EmailMarketingProvider $provider): bool
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
