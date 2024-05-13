<?php

namespace Database\Seeders;

// use App\Models\Faq;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('faqs')->insert([
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'title' => 'General Question',
                'question' => 'What is Productize?',
                'answer' => 'We sell digital Products',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'title' => 'Sellers',
                'question' => 'Our end goals?',
                'answer' => 'To sell and buy products',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
