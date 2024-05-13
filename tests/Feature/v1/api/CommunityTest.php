<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Mail\CommunityWelcomeMail;
use Illuminate\Support\Facades\Mail;
use Faker\Factory as Faker;
use Tests\TestCase;

class CommunityTest extends TestCase
{

    public function test_getAllCommunity(): void
    {
        $response = $this->get('api/community');

        $response->assertStatus(200);
    }


    public function test_storeCommunity(): void
    {
        Mail::fake();
        $faker = Faker::create();

        // create the community member
        $communityData = [
            'email' => $faker->unique()->safeEmail,
        ];

        // Send a POST request to store the community member
        $response = $this->post('api/community/create', $communityData);

        // Assert that the request was successful (status code 201)
        $response->assertStatus(201);

        // Assert that the community member was stored in the database with the provided data
        $this->assertDatabaseHas('communities', [
            'email' => $communityData['email'],
        ]);

        // Assert that the welcome email was sent
        Mail::assertSent(CommunityWelcomeMail::class, function ($mail) use ($communityData) {
            return $mail->hasTo($communityData['email']);
        });
    }
}
