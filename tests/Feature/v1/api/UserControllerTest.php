<?php

namespace Tests\Feature;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Http\Resources\UserResource;
use App\Mail\RequestHelp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_show()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->get('/api/users/me');

        $response
            ->assertStatus(200)
            ->assertJson(
                UserResource::make($user)
                    ->response()
                    ->getData(true)
            );
    }

    public function test_show_unauthenticated()
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()
            ->get('/api/users/me');
    }

    public function test_update()
    {
        $user = User::factory()->create();

        $user->markEmailAsVerified();

        $this->assertTrue($user->hasVerifiedEmail(), true);

        Storage::fake('spaces');

        $logo = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user, 'web')
            ->withoutExceptionHandling()
            ->post('/api/users/me', [
                'logo' => $logo,
                'bio' => 'this is a bio',
                'username' => 'updated',
                'phone_number' => '12345678',
                'bio' => 'bio',
                'twitter_account' => 'https://twitter.com',
                'facebook_account' => 'https://facebook.com',
                'youtube_account' => 'https://youtube.com',
            ]);

        Storage::disk('spaces')->assertExists('avatars/avatar.jpg');

        $updatedUser = User::find($user->id);

        $response->assertStatus(200)
            // ->assertJson(UserResource::make($updatedUser)->response()->getData(true))
            ->assertJsonPath('data.profile_completed', true);
    }

    public function test_update_unauthenticated()
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()
            ->post('/api/users/me');
    }

    public function test_update_invalid_payload()
    {
        $this->expectException(UnprocessableException::class);

        $user = User::factory()->create();

        $user->markEmailAsVerified();

        $this->actingAs($user, 'web')
            ->withoutExceptionHandling()
            ->postJson('/api/users/me', ['full_name' => 10]);
    }

    public function test_change_password()
    {
        $user = User::factory()->create([
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $user->markEmailAsVerified();

        $this->assertTrue($user->hasVerifiedEmail(), true);

        // Generate a valid new password
        $newPassword = $this->faker->regexify('^(?=.*[0-9])[A-Za-z0-9]{8,16}$');

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/users/change-password', [
                'password' => 'password',
                'new_password' => $newPassword,
                'new_password_confirmation' => $newPassword,
            ]);

        $updatedUser = User::find($user->id);

        $this->assertTrue(Hash::check($newPassword, $updatedUser->password));

        $response->assertStatus(200)
            ->assertJson(UserResource::make($updatedUser)->response()->getData(true));
    }

    public function test_change_password_user_email_unverified()
    {
        $this->expectException(ForbiddenException::class);

        $user = User::factory()->create([
            'password' => 'password',
            'email_verified_at' => null,
        ]);

        $newPassword = $this->faker->regexify('^(?=.*[0-9])[A-Za-z0-9]{8,16}$');

        $this->actingAs($user, 'web')
            ->withoutExceptionHandling()
            ->postJson('/api/users/change-password', [
                'password' => 'password',
                'new_password' => $newPassword,
                'new_password_confirmation' => $newPassword,
            ]);
    }

    public function test_change_password_incomplete_payload()
    {
        $this->expectException(UnprocessableException::class);

        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $user->markEmailAsVerified();

        $newPassword = $this->faker->regexify('^(?=.*[0-9])[A-Za-z0-9]{8,16}$');

        $this->actingAs($user, 'web')
            ->withoutExceptionHandling()
            ->postJson('/api/users/change-password', [
                'password' => 'password',
                'new_password' => $newPassword,
            ]);
    }

    public function test_change_password_incorrect_password()
    {
        $this->expectException(BadRequestException::class);

        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $user->markEmailAsVerified();

        // Generate a valid new password
        $newPassword = $this->faker->regexify('^(?=.*[0-9])[A-Za-z0-9]{8,16}$');

        $this->actingAs($user, 'web')
            ->withoutExceptionHandling()
            ->postJson('/api/users/change-password', [
                'password' => 'wrong password',
                'new_password' => $newPassword,
                'new_password_confirmation' => $newPassword,
            ]);
    }

    public function test_requestHelp()
    {
        Mail::fake();

        $user = User::factory()->create();

        $subject = 'My Subject';
        $message = 'message';

        $user->markEmailAsVerified();

        $response = $this->actingAs($user, 'web')
            ->withoutExceptionHandling()
            ->postJson('/api/users/request-help', [
                'email' => $user->email,
                'subject' => $subject,
                'message' => $message,
            ]);

        $mailable = new RequestHelp($user->email, $subject, $message);

        $response->assertStatus(200);

        $mailable->assertSeeInHtml($message);

        $mailable->assertSeeInHtml($subject);

        Mail::assertSent(RequestHelp::class);
    }

    public function test_requestHelp_without_email()
    {
        Mail::fake();

        $user = User::factory()->create();

        $subject = 'My Subject';
        $message = 'message';

        $user->markEmailAsVerified();

        $response = $this->actingAs($user, 'web')
            ->withoutExceptionHandling()
            ->postJson('/api/users/request-help', [
                'subject' => $subject,
                'message' => $message,
            ]);

        $mailable = new RequestHelp($user->email, $subject, $message);

        $response->assertStatus(200);

        $mailable->assertSeeInHtml($message);

        $mailable->assertSeeInHtml($subject);

        Mail::assertSent(RequestHelp::class);
    }

    public function test_update_user_kyc_information()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Storage::fake('spaces');
        $image = UploadedFile::fake()->image('avatar.jpg');

        $data = [
            'country' => $this->faker->country,
            'document_type' => 'National Id card',
            'document_image' => $image,
        ];


        // Send a POST request to the updateKyc endpoint
        $response = $this->postJson(route('user.kyc'), $data);

        // Assert the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert the user's information was updated in the database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'country' => $data['country'],
            'document_type' => $data['document_type'],
        ]);

        // Assert the image was stored
        Storage::disk('spaces')->assertExists('kyc-document/avatar.jpg');
    }

    public function test_validation_errors()
    {
        $response = $this->postJson(route('user.kyc'), [
            'document_type' => 'Invalid Type',
        ]);


        $response->assertStatus(401);
    }

    public function test_file_upload_size_limit()
    {

        $user = User::factory()->create();

        $this->actingAs($user);


        Storage::fake('spaces');

        //create a file that's exactly 2048 KB
        $validFile = UploadedFile::fake()->create('avatar.jpg', 2048);

        $response = $this->postJson(route('user.kyc'), [
            'document_image' => $validFile,
        ]);

        $response->assertStatus(200);

        // file with a size over 2048 KB
        $invalidFile = UploadedFile::fake()->create('avatar.jpg', 2900);

        $response = $this->postJson(route('user.kyc'), [
            'document_image' => $invalidFile,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The document image must not be greater than 2mb'
        ]);
    }

    public function test_update_with_different_document_types()
    {

        $documentTypes = ["Driver's license", "National Id card", "National Passport"];

        foreach ($documentTypes as $type) {


            $user = User::factory()->create();

            $this->actingAs($user);

            $data = ['document_type' => $type];

            $response = $this->postJson(route('user.kyc'), $data);

            $response->assertStatus(200);
            $this->assertDatabaseHas('users', [
                'document_type' => $type,
            ]);
        }
    }
}
