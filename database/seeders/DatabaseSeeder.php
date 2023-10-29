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
        $this->call(class: CustomerSeeder::class);
        $this->call(class: ProductSeeder::class);
        $this->call(class: OrderSeeder::class);
        $this->call(class: UserSeeder::class);
    }
}
