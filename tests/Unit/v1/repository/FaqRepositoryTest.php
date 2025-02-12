<?php

namespace Tests\Unit;

use App\Exceptions\BadRequestException;
use App\Exceptions\ModelCastException;
use App\Models\Faq;
use App\Models\Product;
use App\Repositories\FaqRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private FaqRepository $faqRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faqRepository = new FaqRepository;
    }

    /**
     * Test the create method.
     */
    public function test_create_faq()
    {
        $data = [
            'title' => 'General Question',
            'question' => 'Best Club ?',
            'answer' => 'Real Madrid football club is the best club',
        ];

        $faq = $this->faqRepository->create($data);

        $this->assertInstanceOf(Faq::class, $faq);
        $this->assertEquals($data['title'], $faq->title);
        $this->assertEquals($data['question'], $faq->question);
        $this->assertEquals($data['answer'], $faq->answer);
    }

    /**
     * Test the find method.
     */
    public function test_find_faqs()
    {
        $count = 10;

        Faq::factory()->count($count)->create();

        $faqs = $this->faqRepository->find();

        $this->assertNotEmpty($faqs);
        $this->assertInstanceOf(Faq::class, $faqs->first());
        $this->assertCount(10, $faqs);
    }

    /**
     * Test the findById method.
     */
    public function test_find_faq_by_id()
    {
        $faq = Faq::factory()->create();

        $foundFaq = $this->faqRepository->findById($faq->id);

        $this->assertInstanceOf(Faq::class, $foundFaq);
        $this->assertEquals($faq->id, $foundFaq->id);
    }

    /**
     * Test the update method.
     */
    public function test_update_faq()
    {
        $faq = Faq::factory()->create();

        $updates = [
            'title' => 'General question',
            'question' => 'What is my name?',
            'answer' => 'My name is Oba.',
        ];

        $updatedFaq = $this->faqRepository->update($faq, $updates);

        $this->assertInstanceOf(Faq::class, $updatedFaq);
        $this->assertEquals($updates['question'], $updatedFaq->question);
        $this->assertEquals($updates['answer'], $updatedFaq->answer);
    }

    /**
     * Test the delete method.
     */
    public function test_delete_faq()
    {
        $faq = Faq::factory()->create();

        $result = $this->faqRepository->deleteOne($faq);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_create_faqs_without_title()
    {
        // Attempt to create faq without a title
        $data = [
            'question' => 'How are you?',
            'answer' => 'I am fine',
        ];

        $this->expectException(BadRequestException::class);
        $this->faqRepository->create($data);
    }

    public function test_update_faq_successfully(): void
    {
        // Create faq instance for testing
        $faq = Faq::factory()->create();

        // Define updates for the review
        $updates = [
            'title' => 'General Question',
            'answer' => 'Helloo',
        ];

        // Update the review
        $updatedFaq = $this->faqRepository->update($faq, $updates);

        // Assert the cart was updated successfully
        $this->assertEquals($faq->id, $updatedFaq->id);
        $this->assertEquals($updates['title'], $updatedFaq->title);
        $this->assertEquals($updates['answer'], $updatedFaq->answer);
    }

    public function test_findbyid_return_null_for_when_not_found(): void
    {
        $result = $this->faqRepository->findById('id_does_not_exist');

        $this->assertNull($result);
    }

    public function test_update_with_non_faq_model_throws_model_cast_exception(): void
    {
        $product = Product::factory()->create();

        // Define updates for the faq
        $updates = [
            'title' => 'Product',
            'question' => 'Is it a nice product',
            'answer' => 'It is a nice product',
        ];

        // Expect ModelCastException when trying to update a non-faq model
        $this->expectException(ModelCastException::class);

        // Attempt to update faq instance using the product repository (should throw exception)
        $this->faqRepository->update($product, $updates);
    }

    public function test_query_with_non_existent_faq_title()
    {
        Faq::factory()->count(3)->create();

        $query = $this->faqRepository->query(['title' => 'General Products']);
        $results = $query->get();

        $this->assertCount(0, $results);
    }

    public function test_query_and_find()
    {
        Faq::factory()->count(5)->create(['title' => 'Common Title']);
        Faq::factory()->count(3)->create();

        $query = $this->faqRepository->query(['title' => 'Common Title']);
        $this->assertEquals(5, $query->count());

        $faqs = $this->faqRepository->find(['title' => 'Common Title']);
        $this->assertCount(5, $faqs);
    }
}
