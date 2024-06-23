<?php

namespace Tests\Unit;

use App\Exceptions\ModelCastException;
use App\Models\Complaint;
use App\Models\User;
use App\Repositories\ComplaintRepository;
use Database\Seeders\ComplaintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ComplaintRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ComplaintRepository();
    }

    public function test_create_complaint()
    {
        $data = [
            'user_id' => User::factory()->create()->id,
            'subject' => 'Test Subject',
            'message' => 'Test Message',
            'email' => 'test@example.com',
        ];

        $complaint = $this->repository->create($data);

        $this->assertInstanceOf(Complaint::class, $complaint);
        $this->assertDatabaseHas('complaints', $data);
    }

    public function test_query_complaints_with_filters()
    {
        Complaint::factory()->count(5)->create([
            'created_at' => now()->subDays(2)->endOfDay()
        ]);

        $start_date = now()->subMonth()->startOfDay();
        $end_date = now()->endOfDay();

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        $query = $this->repository->query($filter);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
        $this->assertCount(5, $query->get());
    }

    public function test_find_complaints_with_filters()
    {
        $this->seed(ComplaintSeeder::class);

        $user = User::factory()->create();

        $complaints = Complaint::factory()->count(5)->create([
            'user_id' => $user->id
        ]);

        $filter = ['user_id' => $user->id];

        $complaints = $this->repository->find($filter);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $complaints);
        $this->assertEquals($user->id, $complaints->first()->user->id);
    }

    public function test_find_complaint_by_id()
    {
        $complaint = Complaint::factory()->create();

        $result = $this->repository->findById($complaint->id);

        $this->assertInstanceOf(Complaint::class, $result);
        $this->assertEquals($complaint->id, $result->id);
    }

    public function test_find_one_complaint_with_filters()
    {
        $user = User::factory()->create();

        $complaint = Complaint::factory()->create(['user_id' => $user->id]);

        $filter = ['user_id' => $user->id];

        $result = $this->repository->findOne($filter);

        $this->assertInstanceOf(Complaint::class, $result);
        $this->assertEquals($complaint->id, $result->id);
    }

    public function test_update_complaint()
    {
        $complaint = Complaint::factory()->create();

        $updates = ['subject' => 'Updated Subject'];

        $result = $this->repository->update($complaint, $updates);

        $this->assertInstanceOf(Complaint::class, $result);

        $this->assertEquals('Updated Subject', $result->subject);
    }

    public function test_update_complaint_throws_exception_for_invalid_entity()
    {
        $this->expectException(ModelCastException::class);

        $user = User::factory()->create();

        $updates = ['subject' => 'Updated Subject'];

        $this->repository->update($user, $updates);
    }

    public function test_query_with_empty_filter()
    {
        $this->seed(ComplaintSeeder::class);

        $expected_count = 10; // from the seeder class

        $query = $this->repository->query([]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
        $this->assertCount($expected_count, $query->get());
    }

    public function test_find_with_empty_filter()
    {
        $this->seed(ComplaintSeeder::class);

        $expected_count = 10; // from the seeder class

        $complaints = $this->repository->find(null);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $complaints);
        $this->assertCount($expected_count, $complaints);
    }
}
