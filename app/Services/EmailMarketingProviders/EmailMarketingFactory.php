<?php

namespace App\Services\EmailMarketingProviders;

use App\Enums\EmailMarketingProvider;
use App\Exceptions\ServerErrorException;

class EmailMarketingFactory implements EmailMarketingServiceContract
{
    static function addSubscriber(string $email, string $fullname, string $token, EmailMarketingProvider $provider): bool
    {
        switch ($provider) {
            case EmailMarketingProvider::MailerLite->value:
                return MailerLiteService::addSubscriber($email, $fullname, $token, $provider);

            case EmailMarketingProvider::MailChimp->value:
                return MailchimpService::addSubscriber($email, $fullname, $token, $provider);

            default:
                throw new ServerErrorException("invalid email provider");
        }
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
