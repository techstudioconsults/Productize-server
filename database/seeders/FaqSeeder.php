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
        'title' => "General Question",
        'question' => 'What is Productize?',
        'answer' => 'Productize is a platform for buying and selling',
       ]);

       Faq::factory()->create([
        'title' => "General Question",
        'question' => 'Which club is the best?',
        'answer' => 'Real Madrid football club',
       ]);

       Faq::factory()->create([
        'title' => "Product Question",
        'question' => 'How do we upload products?',
        'answer' => 'Navigate to your user dashboard and head to the create product section.',
       ]);

       Faq::factory(3)->create();
    }
}
