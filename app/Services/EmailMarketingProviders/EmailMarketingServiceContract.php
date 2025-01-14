<?php

namespace App\Services\EmailMarketingProviders;

interface EmailMarketingServiceContract
{
    // data => subscriber, provider, token, campain_id
    static function addSubscriber(array $data): bool;

    static public function removeSubscriber(string $email): bool;

    static function getSubscribers(): array;

    // data => token, provider, name
    static function createCampaign(array $data): string;
}
