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
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillSellingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private SkillSellingRepository $skillSellingRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skillSellingRepository = new SkillSellingRepository;
    }

    public function test_create()
    {
        $product = Product::factory()->create();

        $data = [
            'category' => 'Product',
            'product_id' => $product->id,
            'link' => 'https:github.com/intuneteq',
            'resource_link' => ['https:github.com/obajide028'],
        ];

        $result = $this->skillSellingRepository->create($data);
        $this->assertInstanceOf(SkillSelling::class, $result);
        $this->assertEquals($data['category'], $result->category);
        $this->assertEquals($data['link'], $result->link);
    }

    public function test_query()
    {
        SkillSelling::factory()->count(5)->create(['created_at' => now()->subDays(5)]);
        SkillSelling::factory()->count(5)->create(['created_at' => now()->subDays(10)]);

        $filter = [
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->toDateString(),
        ];

        $query = $this->skillSellingRepository->query($filter);

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertCount(5, $query->get());
    }

    public function test_find()
    {
        SkillSelling::factory()->count(5)->create();

        $products = $this->skillSellingRepository->find();

        $this->assertInstanceOf(Collection::class, $products);
        $this->assertCount(5, $products);
    }

    public function test_find_by_id()
    {
        $product = SkillSelling::factory()->create();
        $foundProduct = $this->skillSellingRepository->findById($product->id);

        $this->assertInstanceOf(SkillSelling::class, $foundProduct);
        $this->assertEquals($product->id, $foundProduct->id);
    }

    public function test_findbyid_return_null_for_when_not_found(): void
    {
        $result = $this->skillSellingRepository->findById('id_does_not_exist');

        $this->assertNull($result);
    }

    public function test_find_one()
    {
        $product = Product::factory()->create();

        $expected = SkillSelling::factory()->create([
            'product_id' => $product->id,
        ]);

        $filter = ['product_id' => $product->id];

        $result = $this->skillSellingRepository->findOne($filter);

        $this->assertInstanceOf(SkillSelling::class, $result);

        $this->assertEquals($product->id, $result->product->id);
        $this->assertEquals($expected->id, $result->id);
    }

    public function test_find_one_returns_null_for_no_match()
    {
        $filter = ['product_id' => 'invalid-id'];

        $foundProduct = $this->skillSellingRepository->findOne($filter);

        $this->assertNull($foundProduct);
    }

    public function test_update()
    {
        $skillSelling = SkillSelling::factory()->create();

        $updates = ['link' => 'https://updated.com'];
        $result = $this->skillSellingRepository->update($skillSelling, $updates);

        $this->assertEquals($skillSelling->id, $result->id);
        $this->assertEquals($updates['link'], $result->link);
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
}
