<?php

namespace App\Services\EmailMarketingProviders;

use App\Enums\EmailMarketingProvider;
use Illuminate\Support\Facades\Http;

class MailerLiteService implements EmailMarketingServiceContract
{
    static function addSubscriber(string $email, array $fullname, string $token, EmailMarketingProvider $provider): bool
    {
        $payload = [
            'email' => $email,
            'fields' => [
                'name' => $fullname['first_name'],
                'last_name' => $fullname['last_name'],
            ]
        ];

        Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
        ])->post('https://connect.mailerlite.com/api/subscribers', $payload)->throw()->json();

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
