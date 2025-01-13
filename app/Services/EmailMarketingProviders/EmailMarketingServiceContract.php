<?php

namespace App\Services\EmailMarketingProviders;

interface EmailMarketingServiceContract
{
    public function addSubscriber(array $data): bool;

    public function removeSubscriber(string $email): bool;

    public function getSubscribers(): array;
}
