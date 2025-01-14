<?php

namespace App\Services\EmailMarketingProviders;

use App\Enums\EmailMarketingProvider;
use MailchimpMarketing\ApiClient;
use Throwable;

class MailchimpService implements EmailMarketingServiceContract
{
    // data => token, provider, name
    static function createCampaign(array $data): string
    {
        $mailchimp = new ApiClient();

        $mailchimp->setConfig([
            'apiKey' => $data['token'],
            'server' => self::getDataCenterFromApiKey($data['token']),
        ]);


        $response = $mailchimp->lists->createList([
            "name" => $data['name'],
            "permission_reminder" => "permission_reminder",
            "email_type_option" => false,
            "contact" => [
                "company" => "",
                "address1" => "",
                "city" => "",
                "state" => "",
                "zip" => "",
                "country" => "",
            ],
            "campaign_defaults" => [
                "from_name" => "",
                "from_email" => "",
                "subject" => $data['name'],
                "language" => "EN_US",
            ],
        ]);

        return $response['id'];
    }

    // data => subscriber, provider, token, campaign_id
    static function addSubscriber(array $data): bool
    {
        $mailchimp = new ApiClient();

        $mailchimp->setConfig([
            'apiKey' => $data['token'],
            'server' => self::getDataCenterFromApiKey($data['token']),
        ]);


        $mailchimp->lists->addListMember($data['campaign_id'], [
            "email_address" => $data['subscriber']['email'],
            "status" => "subscribed",
            "merge_fields" => [
                "FNAME" => $data['subscriber']['fullname']['first_name'],
                "LNAME" => $data['subscriber']['fullname']['last_name']
            ]
        ]);


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

    function getDataCenterFromApiKey(string $apiKey): string
    {
        // Split the API key by the '-' character
        $parts = explode('-', $apiKey);

        // The data center is the second part
        return $parts[1] ?? '';
    }
}
