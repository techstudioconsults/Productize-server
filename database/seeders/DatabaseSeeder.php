<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(class: TruncateSeeder::class);
        $this->call(class: UserSeeder::class);
        $this->call(class: ProductSeeder::class);
        $this->call(class: OrderSeeder::class);
        $this->call(class: PayoutSeeder::class);
        $this->call(class: ReviewSeeder::class);
        $this->call(class: RevenueSeeder::class);
        $this->call(class: ComplaintSeeder::class);
        $this->call(class: FaqSeeder::class);
    }
}
