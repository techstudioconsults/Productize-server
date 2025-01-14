<?php

namespace App\Services\EmailMarketingProviders;

use App\Enums\EmailMarketingProvider;
use App\Exceptions\ServerErrorException;

class EmailMarketingFactory implements EmailMarketingServiceContract
{
    static function createCampaign(array $data): string
    {
        switch ($data['provider']) {
            case EmailMarketingProvider::MailerLite->value:
                return MailerLiteService::createCampaign($data);

            case EmailMarketingProvider::MailChimp->value:
                return MailchimpService::createCampaign($data);

            default:
                throw new ServerErrorException("invalid email provider");
        }
    }

    static function addSubscriber(array $data): bool
    {
        switch ($data['provider']) {
            case EmailMarketingProvider::MailerLite->value:
                return MailerLiteService::addSubscriber($data);

            case EmailMarketingProvider::MailChimp->value:
                return MailchimpService::addSubscriber($data);

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
