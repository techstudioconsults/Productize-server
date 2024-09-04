<?php

namespace Tests\Feature\v1\api;

use App\Http\Resources\ComplaintResource;
use App\Mail\ContactUsMail;
use App\Mail\ContactUsResponseMail;
use App\Mail\LodgeComplaint;
use App\Models\Complaint;
use App\Traits\SanctumAuthentication;
use Database\Seeders\ComplaintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Tests\TestCase;

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
                    'id', 'subject', 'message', 'email', 'created_at', 'user',
                ],
            ],
            'links',
            'meta',
        ]);
    }

    public function test_admin_can_view_complaints()
    {
        $this->actingAsAdmin();

        $this->seed(ComplaintSeeder::class);

        $expected_count = 10; // check seeder. must be 10 so it doesnt affect the test pagination.

        $expected_json = ComplaintResource::collection(Complaint::all())->response()->getData(true);

        $response = $this->withoutExceptionHandling()->get(route('complaints.index'));

        $response->assertOk()->assertJson($expected_json, true);
        $this->assertCount($expected_count, $response->json('data'));

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'subject', 'message', 'email', 'created_at', 'user',
                ],
            ],
            'links',
            'meta',
        ]);
    }

    public function test_non_super_admin_cannot_view_complaints()
    {
        $this->actingAsRegularUser();

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
            'message' => 'Test Message',
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
            'message' => 'Test Message',
        ];

        $response = $this->withoutExceptionHandling()->post(route('complaints.store'), $data);

        $response->assertCreated();
        $this->assertDatabaseHas('complaints', [
            'subject' => 'Test Subject',
            'message' => 'Test Message',
            'email' => $user->email,
        ]);
    }

    public function test_guest_cannot_lodge_complaint()
    {
        $data = [
            'subject' => 'Test Subject',
            'message' => 'Test Message',
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
                'id', 'subject', 'message', 'email', 'user', 'created_at',
            ],
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

    public function test_contact()
    {
        Mail::fake();

        $formData = [
            'firstname' => 'Babajide',
            'lastname' => 'Odesanya',
            'email' => 'obajide028@gmail.com',
            'subject' => 'Quality Assurance',
            'message' => 'How do i go it?',
        ];

        $response = $this->postJson('/api/complaints/contact-us', $formData);

        $response->assertStatus(200)
            ->assertJson(['Message' => 'Your message has been sent.']);

        Mail::assertSent(ContactUsMail::class, function ($mail) {
            return $mail->hasTo(env('CONTACT_EMAIL', 'info@trybytealley.com'));
        });

        Mail::assertSent(ContactUsResponseMail::class, function ($mail) use ($formData) {
            return $mail->hasTo($formData['email']);
        });
    }

    public function test_contact_form_validation()
    {
        $response = $this->postJson('/api/complaints/contact-us', []);

        $response->assertStatus(422);
    }

    public function test_contact_form_validation_fails_with_invalid_email()
    {
        Mail::fake();

        $formData = [
            'firstname' => 'Babajide',
            'lastname' => 'Odesanya',
            'subject' => 'Quality Assurance',
            'message' => 'How do i go it?',
        ];

        $response = $this->postJson('/api/complaints/contact-us', $formData);

        $response->assertStatus(422);
    }

    public function test_contact_form_validation_fails_with_missing_fields()
    {
        $formData = [
            'firstname' => 'jide',
            'email' => 'obajide028@gmail.com',
        ];

        $response = $this->postJson('/api/complaints/contact-us', $formData);

        $response->assertStatus(422);
    }
}
