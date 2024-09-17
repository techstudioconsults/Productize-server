<?php

namespace Database\Seeders;

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'full_name' => 'Tobi Olanitori',
            'email' => 'trybytealley@gmail.com',
            'role' => Roles::SUPER_ADMIN->value,
            'password' => '12345',
        ]);
    }
}
