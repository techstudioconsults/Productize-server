<?php

namespace App\Services\EmailMarketingProviders;

use App\Enums\EmailMarketingProvider;

interface EmailMarketingServiceContract
{
    static function addSubscriber(string $email, string $fullname, string $token, EmailMarketingProvider $provider): bool;

    static public function removeSubscriber(string $email): bool;

    static function getSubscribers(): array;
}
