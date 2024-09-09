<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 09-05-2024
 */

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Faq::factory()->create([
            'title' => 'General Question',
            'question' => 'What is Bytealley?',
            'answer' => 'Bytealley is a digital marketplace where creators can sell their products, such as art, design, resources, eBooks and more.',
        ]);

        Faq::factory()->create([
            'title' => 'General Question',
            'question' => 'How do I get started as creator?',
            'answer' => 'To start selling on Bytealley, sign up for an account, create your seller/buyer profile, and begin uploading/buying your products.',
        ]);

        Faq::factory()->create([
            'title' => 'General Question',
            'question' => 'Is it free to join as a creator?',
            'answer' => 'Yes, it is free to join and set up your creator account. However, you will placed on a free trial for a period of time after which you are asked to subscribe.',
        ]);

        Faq::factory()->create([
            'title' => 'General Question',
            'question' => 'What types of digital products can I sell?',
            'answer' => 'You can sell a wide range of digital products, including art, photography, music, software, templates, eBooks, and more. If it is a digital creation, it is welcome here!',
        ]);

        Faq::factory()->create([
            'title' => 'Selling on Bytealley',
            'question' => 'How do I set the price for my digital products?',
            'answer' => 'You have the flexibility to set your prices, as well as discounted prices. We recommend researching the market and pricing competitively.',
        ]);

        Faq::factory()->create([
            'title' => 'Selling on Bytealley',
            'question' => 'How and when do I get paid for my sales?',
            'answer' => 'You will receive payments through your chosen payment account via paystack. Detailed information can be found in your account dashboard.',
        ]);

        Faq::factory()->create([
            'title' => 'Selling on Bytealley',
            'question' => 'Is there a limit on the number of products I can sell?',
            'answer' => 'No, there are no restrictions on the number of products you can sell. Create as many as you like!',
        ]);
        Faq::factory()->create([
            'title' => 'Buyer Questions',
            'question' => 'Is it safe to buy digital products on Productize?',
            'answer' => 'Yes, we prioritize security. We use secure payment gateways and encryption to protect your transactions.',
        ]);
        Faq::factory()->create([
            'title' => 'Buyer Questions',
            'question' => 'What happens after I purchase a digital product?',
            'answer' => 'After purchase, you will have instant access to your purchased product via the download page on your dashboard.',
        ]);
        Faq::factory()->create([
            'title' => 'Buyer Questions',
            'question' => 'Do I own the rights to the digital products I buy?',
            'answer' => 'Ownership terms vary by product and are specified by the creator. Make sure to review the product\'s description for details.',
        ]);
        Faq::factory()->create([
            'title' => 'Support and Assistance',
            'question' => 'I have a problem with my account or a purchase. How can I get help?',
            'answer' => 'You can contact our dedicated support team through the "Contact Us" link on our website or through the help page on your dashboard, and we will be happy to assist you.',
        ]);

        Faq::factory()->create([
            'title' => 'Support and Assistance',
            'question' => 'What if I have more questions that aren\'t answered here?',
            'answer' => 'If you have additional questions, please don\'t hesitate to reach out to us via mail or our social media channels.',
        ]);

        Faq::factory()->create();
    }
}
