<?php

namespace Database\Seeders;

use App\Models\SkillSelling;
use Illuminate\Database\Seeder;

class SkillSellingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SkillSelling::factory(5)->create();
    }
}
