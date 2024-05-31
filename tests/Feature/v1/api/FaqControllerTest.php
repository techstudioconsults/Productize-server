<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 09-05-2024
 */

namespace Tests\Feature;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Faker\Factory as Faker;

class FaqControllerTest extends TestCase
{

    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    // public function test_example(): void
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }

    public function test_getAllFaq(): void
    {
        $response = $this->get('api/faqs');

        $response->assertStatus(200);
    }

    public function test_storeFaq(): void
    {
        // Generate new data for creating the faq
        $faqData = [
            'title' => 'General Question',
            'question' => 'What is my name?',
            'answer' => 'My name is oba',
        ];

        // Send a POST request to store the faq
        $response = $this->post('api/faqs/create', $faqData);

        // Assert that the request was successful (status code 201)
        $response->assertStatus(201);

        // Assert that the faq was stored in the database with the provided data
        $this->assertDatabaseHas('faqs', [
            'title' => $faqData['title'],
            'question' => $faqData['question'],
            'answer' => $faqData['answer']
        ]);
    }

    public function test_updateFaq(): void
    {
        // Create a faq
        $faqData = Faq::factory()->create([
            'title' => 'Generalss',
            'question' => 'What is Product?',
            'answer' => 'Product is an ecommerce application',
        ]);

        // Generate new data for updating the faq
        $newFaqData = [
            'title' => 'General',
            'question' => 'What is productize?',
            'answer' => 'Productize is an ecommerce application'
        ];

        // send a PUT request to update the user
        $response = $this->put('api/faqs/' . $faqData->id, $newFaqData);

        // Assert that the request was successful (status code 200)
        $response->assertStatus(200);

        // Assert that the faq was updated with the new data
        $this->assertDatabaseHas('faqs', [
            'id' => $faqData->id,
            'title' => $newFaqData['title'],
            'question' => $newFaqData['question'],
            'answer' => $newFaqData['answer'],
        ]);
    }

    public function test_destroyFaq(): void
    {
        // create a faq
        $faq = Faq::factory()->create([
            'title' => 'Generalss',
            'question' => 'What is Product?',
            'answer' => 'Product is an ecommerce application',
        ]);

        // Send a DELETE request to delete the faq
        $response = $this->delete('api/faqs/' . $faq->id);

        // Assert that the request was successful (status code 200)
        $response->assertStatus(200);

        // Assert that the faq no longer exists in the datbase
        $this->assertDatabaseMissing('faqs', [
            'id' => $faq->id,
        ]);
    }
}
