<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Traits\DisableForeignKeys;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use TruncateTable, DisableForeignKeys;

    public function run(): void
    {
        $this->disableForeignKeys();
        $this->truncate('users');

        User::factory()->create([
            'full_name' => 'Kingsley Solomon',
            'email' => 'kinxly@gmail.com',
        ]);

        User::factory()->create([
            'full_name' => 'Kingsley Solomon Free',
            'email' => 'kinxly@testemail.com',
        ]);

        User::factory()->create([
            'full_name' => 'Tobi Olanitori',
            'email' => 'tobiolanitori1@gmail.com',
        ]);

        User::factory(2)->create();
        $this->enableForeignKeys();
    }
}
