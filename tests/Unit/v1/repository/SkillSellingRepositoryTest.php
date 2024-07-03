<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 02-07-2024
 */

namespace Tests\Unit;

use App\Exceptions\ModelCastException;
use App\Models\Product;
use App\Models\SkillSelling;
use App\Repositories\SkillSellingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillSellingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private SkillSellingRepository $skillSellingRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skillSellingRepository = new SkillSellingRepository();
    }

    public function testCreateSkillSelling()
    {
        $product = Product::factory()->create();

        $data = [
            'category' => 'Product',
            'product_id' => $product->id,
            'level' => 'high',
            'availability' => 'yes',
            'link' => 'https:github.com/intuneteq',
        ];

        $result = $this->skillSellingRepository->create($data);
        $this->assertInstanceOf(SkillSelling::class, $result);
        $this->assertEquals($data['category'], $result->category);
        $this->assertEquals($data['level'], $result->level);
        $this->assertEquals($data['availability'], $result->availability);
        $this->assertEquals($data['link'], $result->link);

    }

    public function test_findbyid_return_null_for_when_not_found(): void
    {
        $result = $this->skillSellingRepository->findById('id_does_not_exist');

        $this->assertNull($result);
    }

    public function testFindById()
    {
        $skillSelling = SkillSelling::factory()->create();

        $result = $this->skillSellingRepository->findById($skillSelling->id);

        $this->assertInstanceOf(SkillSelling::class, $result);
        $this->assertEquals($skillSelling->id, $result->id);

    }

    public function testUpdate()
    {
        $skillSelling = SkillSelling::factory()->create();

        $updates = ['availability' => 'no'];
        $result = $this->skillSellingRepository->update($skillSelling, $updates);

        $this->assertEquals($skillSelling->id, $result->id);
        $this->assertEquals($updates['availability'], $result->availability);
    }

    public function testFindOne()
    {
        SkillSelling::factory()->count(3)->create();

        $skillSelling = SkillSelling::factory()->create(['level' => 'medium']);

        $result = $this->skillSellingRepository->findOne(['level' => 'medium']);

        $this->assertInstanceOf(SkillSelling::class, $result);
        $this->assertEquals($skillSelling->id, $result->id);
    }

    public function testQuery()
    {
        SkillSelling::factory()->count(5)->create();

        $filter = ['level' => 'high'];
        $query = $this->skillSellingRepository->query($filter);

        // $this->assertInstanceOf(SkillSelling::class, $query);
        $this->assertEquals(5, $query->count());
    }

    public function test_update_with_non_skillselling_model_throws_model_cast_exception(): void
    {
        $product = Product::factory()->create();

        // Define updates for the faq
        $updates = [
            'level' => 'high',
        ];

        // Expect ModelCastException when trying to update a non-skillselling model
        $this->expectException(ModelCastException::class);

        // Attempt to update skillselling instance using the product repository (should throw exception)
        $this->skillSellingRepository->update($product, $updates);
    }

    public function testQueryWithNonExistentSkillSellingAvaliability()
    {
        SkillSelling::factory()->count(3)->create();

        $query = $this->skillSellingRepository->query(['availability' => 'General Products']);
        $results = $query->get();

        $this->assertCount(0, $results);
    }

    public function testQueryAndFind()
    {
        SkillSelling::factory()->count(5)->create(['level' => 'small']);
        SkillSelling::factory()->count(3)->create();

        $query = $this->skillSellingRepository->query(['level' => 'small']);
        $this->assertEquals(5, $query->count());

        $faqs = $this->skillSellingRepository->find(['level' => 'high']);
        $this->assertCount(3, $faqs);
    }
}
