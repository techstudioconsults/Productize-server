<?php

namespace App\Services\EmailMarketingProviders;

use Illuminate\Support\Facades\Http;

class MailerLiteService implements EmailMarketingServiceContract
{
    // data => token, provider, name
    public static function createCampaign(array $data): string
    {
        $payload = [
            'name' => $data['name'],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$data['token'],
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
        ])->post('https://connect.mailerlite.com/api/groups', $payload)->throw()->json();

        return $response['data']['id'];
    }

    // data => subscriber, provider, token, campaign_id
    public static function addSubscriber(array $data): bool
    {
        $payload = [
            'email' => $data['subscriber']['email'],
            'fields' => [
                'name' => $data['subscriber']['fullname']['first_name'],
                'last_name' => $data['subscriber']['fullname']['last_name'],
            ],
            'groups' => [$data['campaign_id']],
        ];

        Http::withHeaders([
            'Authorization' => 'Bearer '.$data['token'],
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
        ])->post('https://connect.mailerlite.com/api/subscribers', $payload)->throw()->json();

        return true;
    }

    public static function removeSubscriber(string $email): bool
    {
        return true;
    }

    public static function getSubscribers(): array
    {
        return [];
    }
}
