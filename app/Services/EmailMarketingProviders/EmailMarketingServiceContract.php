<?php

namespace App\Services\EmailMarketingProviders;

interface EmailMarketingServiceContract
{
    // data => subscriber, provider, token, campain_id
    public static function addSubscriber(array $data): bool;

    public static function removeSubscriber(string $email): bool;

    public static function getSubscribers(): array;

    // data => token, provider, name
    public static function createCampaign(array $data): string;
}
