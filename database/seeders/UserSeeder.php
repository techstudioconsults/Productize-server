<?php

namespace Database\Seeders;

use App\Enums\Roles;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'full_name' => 'Kingsley Solomon',
            'email' => 'kinxly@gmail.com',
            'first_product_created_at' => Carbon::now(),
        ]);

        User::factory()->create([
            'full_name' => 'Kingsley Solomon Free',
            'email' => 'kinxly@testemail.com',
            'account_type' => 'free',
        ]);

        User::factory()->create([
            'full_name' => 'Tobi Olanitori',
            'email' => 'tobiolanitori1@gmail.com',
            'first_product_created_at' => Carbon::now(),
        ]);
        User::factory()->create([
            'full_name' => 'Tobi Olanitori',
            'email' => 'tobi.olanitori.binaryartinc@gmail.com',
            'role' => Roles::SUPER_ADMIN->value,
            'password' => '12345',
        ]);

        User::factory(5)->create();
    }
}
