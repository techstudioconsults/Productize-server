<?php

namespace Tests\Feature;

use App\Http\Resources\ComplaintResource;
use App\Mail\LodgeComplaint;
use App\Models\Complaint;
use Database\Seeders\ComplaintSeeder;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Tests\Traits\SanctumAuthentication;

class ComplaintControllerTest extends TestCase
{
    use RefreshDatabase, SanctumAuthentication;

    public function test_super_admin_can_view_complaints()
    {
        $this->actingAsSuperAdmin();

        $this->seed(ComplaintSeeder::class);

        $expected_count = 10; // check seeder. must be 10 so it doesnt affect the test pagination.

        $expected_json = ComplaintResource::collection(Complaint::all())->response()->getData(true);

        $response = $this->withoutExceptionHandling()->get(route('complaints.index'));

        $response->assertOk()->assertJson($expected_json, true);
        $this->assertCount($expected_count, $response->json('data'));

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'subject', 'message', 'email', 'created_at', 'user'
                ]
            ],
            'links',
            'meta'
        ]);
    }

    public function test_non_super_admin_cannot_view_complaints()
    {
        $this->actingAsAdmin();

        $response = $this->get(route('complaints.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_complaints()
    {
        $response = $this->get(route('complaints.index'));

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_lodge_complaint()
    {
        Mail::fake();

        $user = $this->actingAsRegularUser();

        $data = [
            'subject' => 'Test Subject',
            'message' => 'Test Message'
        ];

        $response = $this->withoutExceptionHandling()->post(route('complaints.store'), $data);

        $response->assertCreated();
        $response->assertJson(['message' => 'email sent']);

        $this->assertDatabaseHas('complaints', array_merge($data, ['user_id' => $user->id]));

        Mail::assertSent(LodgeComplaint::class);
    }


    public function test_complaint_email_defaults_to_authenticated_user_email()
    {
        $user = $this->actingAsRegularUser();

        $data = [
            'subject' => 'Test Subject',
            'message' => 'Test Message'
        ];

        $response = $this->withoutExceptionHandling()->post(route('complaints.store'), $data);

        $response->assertCreated();
        $this->assertDatabaseHas('complaints', [
            'subject' => 'Test Subject',
            'message' => 'Test Message',
            'email' => $user->email
        ]);
    }

    public function test_guest_cannot_lodge_complaint()
    {
        $data = [
            'subject' => 'Test Subject',
            'message' => 'Test Message'
        ];

        $response = $this->post(route('complaints.store'), $data);

        $response->assertUnauthorized();
    }

    public function test_super_admin_can_view_specific_complaint()
    {
        $this->actingAsSuperAdmin();

        $complaint = Complaint::factory()->create();

        $expected_json = ComplaintResource::make($complaint)->response()->getData(true);

        $response = $this->withoutExceptionHandling()->get(route('complaints.show', $complaint));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id', 'subject', 'message', 'email', 'user', 'created_at'
            ]
        ]);

        $response->assertJson($expected_json, true);
    }

    public function test_non_super_admin_cannot_view_specific_complaint()
    {
        $this->actingAsRegularUser();

        $complaint = Complaint::factory()->create();

        $response = $this->getJson(route('complaints.show', $complaint));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_specific_complaint()
    {
        $complaint = Complaint::factory()->create();

        $response = $this->getJson(route('complaints.show', $complaint));

        $response->assertUnauthorized();
    }
}
