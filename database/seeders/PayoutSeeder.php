<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Payout;
use App\Models\User;
use Database\Seeders\Traits\DisableForeignKeys;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;

class PayoutSeeder extends Seeder
{
    use DisableForeignKeys, TruncateTable;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Payout::factory()->count(10)->create([
            'account_id' => Account::factory()->create(['user_id' => User::factory()->create()->id]),
        ]);
    }
}
