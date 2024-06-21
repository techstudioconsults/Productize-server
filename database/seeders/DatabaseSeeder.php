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
        $this->call(class: UserSeeder::class);
        $this->call(class: OrderSeeder::class); // run orders before product because orders table will truncate and undo all from products
        $this->call(class: ProductSeeder::class);
        $this->call(class: PayoutSeeder::class);
        $this->call(class: ReviewSeeder::class);
    }
}
