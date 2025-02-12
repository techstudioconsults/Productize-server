<?php

namespace Tests\Unit;

use App\Exceptions\BadRequestException;
use App\Models\Community;
use App\Repositories\CommunityRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CommunityRepository $communityRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->communityRepository = new CommunityRepository;
    }

    /**
     * Test the create method
     */
    public function test_create_community()
    {

        $data = [
            'email' => 'odesanya28@gmail.com',
        ];

        $community = $this->communityRepository->create($data);

        $this->assertInstanceOf(Community::class, $community);
        $this->assertEquals($data['email'], $community->email);
    }

    /**
     * Test to find all community members
     */
    public function test_find_community_members()
    {
        $count = 10;

        Community::factory()->count($count)->create();

        $community = $this->communityRepository->find();

        $this->assertNotEmpty($community);
        $this->assertInstanceOf(Community::class, $community->first());
        $this->assertCount(10, $community);
    }

    public function test_create_community_without_email()
    {
        // Attempt to create community without a email
        $data = [];

        $this->expectException(BadRequestException::class);
        $this->communityRepository->create($data);
    }

    public function test_findbyid_return_null_for_when_not_found(): void
    {
        $result = $this->communityRepository->findById('id_does_not_exist');

        $this->assertNull($result);
    }

    public function test_find_community_by_id()
    {
        $community = Community::factory()->create();

        $foundCommunityMember = $this->communityRepository->findById($community->id);
        $this->assertInstanceOf(Community::class, $foundCommunityMember);
        $this->assertEquals($community->id, $foundCommunityMember->id);
    }
}
