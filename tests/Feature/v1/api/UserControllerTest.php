<?php

namespace Tests\Feature;

use App\Enums\Roles;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Http\Resources\UserResource;
use App\Mail\AdminDeletedMail;
use App\Mail\AdminRevokedMail;
use App\Mail\AdminUpdateMail;
use App\Mail\AdminWelcomeMail;
use App\Models\Order;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Traits\SanctumAuthentication;
use Database\Seeders\PayoutSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mail;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;
    use SanctumAuthentication;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_index_with_super_admin()
    {
        $this->seed(UserSeeder::class);

        $this->actingAsSuperAdmin();

        $expected_count = 10; // 9 from the seeder + 1 sanctum generated super admin - Ensure it is 10 cause of pagination

        $expected_json = UserResource::collection(User::all())->response()->getData(true);

        $response = $this->withoutExceptionHandling()->get(route('users.index'));

        $response->assertOk()->assertJson($expected_json, true);
        $response->assertJsonStructure(['data', 'links', 'meta']);
        $this->assertCount($expected_count, $response->json('data')); // Default pagination count
    }

    public function test_index_with_admin()
    {
        $this->seed(UserSeeder::class);

        $this->actingAsAdmin();

        $expected_count = 10; // 9 from the seeder + 1 sanctum generated super admin - Ensure it is 10 cause of pagination

        $expected_json = UserResource::collection(User::all())->response()->getData(true);

        $response = $this->withoutExceptionHandling()->get(route('users.index'));

        $response->assertOk()->assertJson($expected_json, true);
        $response->assertJsonStructure(['data', 'links', 'meta']);
        $this->assertCount($expected_count, $response->json('data')); // Default pagination count
    }

    public function test_index_with_user_not_super_admin()
    {
        $this->actingAsRegularUser();

        $this->expectException(ForbiddenException::class);

        $this->withoutExceptionHandling()->get(route('users.index'));
    }

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

        $response = $this->withoutExceptionHandling()->actingAs($user, 'web')
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

    public function test_stat_with_super_admin(): void
    {
        $this->actingAsSuperAdmin();

        $this->seed([
            UserSeeder::class,
            ProductSeeder::class,
            PayoutSeeder::class,
        ]);

        Order::factory()->count(10)->create();

        $response = $this->withoutExceptionHandling()->get(route('users.stats.admin'));

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'total_products',
                'total_sales',
                'total_payouts',
                'total_users',
                'total_subscribed_users',
                'total_trial_users',
                'conversion_rate',
            ],
        ]);
    }

    public function test_stat_with_admin(): void
    {
        $this->actingAsAdmin();

        $this->seed([
            UserSeeder::class,
            ProductSeeder::class,
            PayoutSeeder::class,
        ]);

        Order::factory()->count(10)->create();

        $response = $this->withoutExceptionHandling()->get(route('users.stats.admin'));

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'total_products',
                'total_sales',
                'total_payouts',
                'total_users',
                'total_subscribed_users',
                'total_trial_users',
                'conversion_rate',
            ],
        ]);
    }

    public function test_stat_without_super_admin()
    {
        $this->actingAsRegularUser();

        $this->expectException(ForbiddenException::class);

        $this->withoutExceptionHandling()->get(route('users.stats.admin'));
    }

    public function test_can_download_users_csv_as_super_admin()
    {
        $this->actingAsSuperAdmin();

        // Create some users
        $this->seed(UserSeeder::class);

        // Call the download endpoint
        $response = $this->withoutExceptionHandling()->get(route('users.download'));

        // Assert response is successful and CSV headers are correct
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=users_'.now()->format('d_F_Y').'.csv');
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
        $response = $this->postJson(route('users.kyc'), $data);

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
        $response = $this->postJson(route('users.kyc'), [
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

        $response = $this->postJson(route('users.kyc'), [
            'document_image' => $validFile,
            'country' => 'Nigeria',
            'document_type' => 'National Passport',
        ]);

        $response->assertStatus(200);

        // file with a size over 2048 KB
        $invalidFile = UploadedFile::fake()->create('avatar.jpg', 2900);

        $response = $this->postJson(route('users.kyc'), [
            'document_image' => $invalidFile,
            'country' => 'Nigeria',
            'document_type' => 'National Passport',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The document image must not be greater than 2mb',
        ]);
    }

    public function test_update_with_different_document_types()
    {

        $documentTypes = ["Driver's license", 'National Id card', 'National Passport'];

        foreach ($documentTypes as $type) {

            $user = User::factory()->create();

            Storage::fake('spaces');

            //create a file that's exactly 2048 KB
            $validFile = UploadedFile::fake()->create('avatar.jpg', 2048);

            $this->actingAs($user);

            $data = [
                'document_type' => $type,
                'country' => 'Nigeria',
                'document_image' => $validFile,
            ];

            $response = $this->postJson(route('users.kyc'), $data);

            $response->assertStatus(200);
            $this->assertDatabaseHas('users', [
                'document_type' => $type,
            ]);
        }
    }

    public function test_super_admin_can_create_admin()
    {
        Mail::fake();

        // Create a super admin user
        $superAdmin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
        ]);

        // Act as the super admin
        $this->actingAs($superAdmin, 'web');

        // Define the user data to create
        $userData = [
            'email' => 'obajide028@gmail.com',
            'full_name' => 'Babajide Odesanya',
            'password' => 'Babajide1.',
            'password_confirmation' => 'Babajide1.',
            'role' => 'ADMIN',
        ];

        // Send a POST request to the users.store route
        $response = $this->postJson(route('users.store'), $userData);

        $response->assertStatus(201);

        Mail::assertSent(AdminWelcomeMail::class, function ($mail) use ($userData) {
            return $mail->hasTo($userData['email']);
        });
    }

    public function test_regular_user_cannot_create_admin()
    {
        $this->actingAsAdmin();

        // Define the user data to create
        $userData = [
            'email' => 'obajide028@gmail.com',
            'full_name' => 'Babajide Odesanya',
            'password' => 'Babajide1.',
            'password_confirmation' => 'Babajide1.',
            'role' => 'ADMIN',
        ];

        // Send a POST request to the users.store route
        $response = $this->postJson(route('users.store'), $userData);

        // Assert the response
        $response->assertStatus(403);
    }

    public function test_super_admin_can_update_user()
    {
        Mail::fake();

        // create user
        $user = User::factory()->create([
            'email' => 'obajide028@gmail.com',
            'full_name' => 'Babajide Odesanya',
            'password' => 'Babajide1.',
        ]);

        // create a super admin
        $superAdmin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
        ]);

        $this->actingAs($superAdmin, 'web');

        $updatedData = [
            'password' => 'May1234567..',
        ];

        // Send a PUT request to the updateAdmin endpoint
        $response = $this->putJson(route('users.updateAdmin', $user->id), $updatedData);

        // Assert the response
        $response->assertStatus(200);

        // Assert that the email was sent
        Mail::assertSent(AdminUpdateMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_revoke_admin_role()
    {
        // Mock the user repository
        $userRepository = $this->createMock(UserRepository::class);
        $this->app->instance(UserRepository::class, $userRepository);

        // create a user with admin role
        $user = User::factory()->create([
            'role' => 'ADMIN',
        ]);

        // Expect the guardedUpdate method to be called once with the right arguments
        $userRepository->expects($this->once())
            ->method('guardedUpdate')
            ->with($user->email, 'role', Roles::USER->value);

        // Fake the mail sending
        Mail::fake();

        // Call the revokeAdminRole method
        $response = $this->actingAs($user)->patch(route('users.revoke-admin-role', $user->id));

        // Assert that the role was updated
        $this->assertTrue(true);

        // Assert that the mail was sent
        Mail::assertSent(AdminRevokedMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // Assert the response
        $response->assertStatus(200);
    }

    public function test_super_admin_can_delete_admin()
    {
        //create admin user
        $admin = User::factory()->create([
            'role' => 'ADMIN',
        ]);

        //create super admin user
        $superAdmin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
        ]);

        // Mock the user repository
        $userRepository = $this->createMock(UserRepository::class);
        $this->app->instance(UserRepository::class, $userRepository);

        $userRepository->expects($this->once())
            ->method('findById')
            ->with($admin->id)
            ->willReturn($admin);

        $userRepository->expects($this->once())
            ->method('deleteOne')
            ->with($this->callback(function ($user) use ($admin) {
                return $user->id === $admin->id;
            }));

        // Fake the mail sending
        Mail::fake();

        // Act as the super admin and call the delete-admin route
        $response = $this->actingAs($superAdmin)
            ->deleteJson(route('users.delete-admin', $admin->id));

        // Assert that the mail was sent
        Mail::assertSent(AdminDeletedMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });

        // Assert the response
        $response->assertStatus(200);
    }

    public function test_can_download_admin_csv_as_super_admin()
    {
        $this->actingAsSuperAdmin();

        // Create some users
        $this->seed(UserSeeder::class);

        // Call the download endpoint
        $response = $this->withoutExceptionHandling()->get(route('users.download-admin'));

        // Assert response is successful and CSV headers are correct
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=admin_users_'.now()->format('d_F_Y').'.csv');
    }
}
