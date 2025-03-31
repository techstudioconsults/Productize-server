<?php

namespace App\Services\EmailMarketingProviders;

use App\Enums\EmailMarketingProvider;
use App\Exceptions\ServerErrorException;

class EmailMarketingFactory implements EmailMarketingServiceContract
{
    public static function createCampaign(array $data): string
    {
        switch ($data['provider']) {
            case EmailMarketingProvider::MailerLite->value:
                return MailerLiteService::createCampaign($data);

            case EmailMarketingProvider::MailChimp->value:
                return MailchimpService::createCampaign($data);

            default:
                throw new ServerErrorException('invalid email provider');
        }
    }

    public static function addSubscriber(array $data): bool
    {
        switch ($data['provider']) {
            case EmailMarketingProvider::MailerLite->value:
                return MailerLiteService::addSubscriber($data);

            case EmailMarketingProvider::MailChimp->value:
                return MailchimpService::addSubscriber($data);

            default:
                throw new ServerErrorException('invalid email provider');
        }
    }

    public static function removeSubscriber(string $email): bool
    {
        return true;
    }

    public static function getSubscribers(): array
    {
        return [];
    }

    public static function validateToken(array $data): bool
    {
        $provider = $data['provider'];

        switch ($provider) {
            case EmailMarketingProvider::MailerLite->value:
                return MailerLiteService::validateToken($data);

            case EmailMarketingProvider::MailChimp->value:
                return MailchimpService::validateToken($data);

            default:
                throw new ServerErrorException('invalid provider');
        }
    }
}
