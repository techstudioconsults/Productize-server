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

        $user = User::where('email', 'tobi.olanitori.binaryartinc@gmail.com')->firstOr(function () {
            return User::factory()->create([
                'email' => 'tobi.olanitori.binaryartinc@gmail.com',
                'full_name' => 'Tobi Olanitori',
            ]);
        });

        Payout::factory(10)->create([
            'account_id' => Account::factory()->create(['user_id' => $user->id]),
        ]);

        Payout::factory()->count(10)->create([
            'account_id' => Account::factory()->create(['user_id' => User::factory()->create()->id]),
        ]);
    }
}
