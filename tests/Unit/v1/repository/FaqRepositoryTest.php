<?php

namespace Tests\Unit;

use App\Models\Faq;
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
        $this->faqRepository = new FaqRepository();
    }

    /**
     * Test the create method.
     */
    public function testCreateFaq()
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
    public function testFindFaqs()
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
    public function testFindFaqById()
    {
        $faq = Faq::factory()->create();

        $foundFaq = $this->faqRepository->findById($faq->id);

        $this->assertInstanceOf(Faq::class, $foundFaq);
        $this->assertEquals($faq->id, $foundFaq->id);
    }

    /**
     * Test the update method.
     */
    public function testUpdateFaq()
    {
        $faq = Faq::factory()->create();

        $updates = [
            'title' => "General question",
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
    public function testDeleteFaq()
    {
        $faq = Faq::factory()->create();

        $result = $this->faqRepository->deleteOne($faq);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }
}