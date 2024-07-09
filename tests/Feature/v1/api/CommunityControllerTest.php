<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 09-05-2024
 */

namespace Tests\Feature;

use App\Mail\CommunityWelcomeMail;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommunityControllerTest extends TestCase
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

    public function test_it_fails_to_store_a_community_member_with_invalid_email()
    {
        Mail::fake();
        $invalidData = [
            'email' => 'not-an-email',
        ];

        // Send a POST request to store the community member
        $response = $this->postJson('api/community/create', $invalidData);

        // Assert that the request failed (status code 422)
        $response->assertStatus(422);
    }

    public function test_it_fails_to_store_a_community_member_with_empty_email()
    {
        Mail::fake();
        $emptyEmailData = [];

        // Send a POST request to store the community member
        $response = $this->postJson('api/community/create', $emptyEmailData);

        // Assert that the request failed (status code 422)
        $response->assertStatus(422);
    }
}
