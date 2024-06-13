<?php

namespace Tests\Unit;

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
   * 
   */
  public function testCreateCommunity()
  {

    $data = [
        'email' => "odesanya28@gmail.com",
    ];

    $community = $this->communityRepository->create($data);

    $this->assertInstanceOf(Community::class, $community);
    $this->assertEquals($data['email'], $community->email);
  }

  /**
   * Test to find all community members
   * 
   */
  public function testFindCommunityMembers()
  {
    $count = 10;

    Community::factory()->count($count)->create();

    $community = $this->communityRepository->find();

    $this->assertNotEmpty($community);
    $this->assertInstanceOf(Community::class, $community->first());
    $this->assertCount(10, $community);
  }


}
