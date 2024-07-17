<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Traits\DisableForeignKeys;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    use DisableForeignKeys, TruncateTable;

    public function run(): void
    {
        $user = User::factory()->create([
            'email' => 'kinxly@gmail.com',
            'full_name' => 'Kingsley Solomon',
        ]);

        $startDate = Carbon::create(2024, 6, 1);
        $endDate = Carbon::create(2024, 6, 30);

        while ($startDate <= $endDate) {
            Order::factory(10)->create([
                'product_id' => Product::factory()->create(['user_id' => $user->id]),
                'created_at' => $startDate,
                'updated_at' => $startDate,
            ]);

            $startDate->addDay();
        }

        Order::factory(10)->create([
            'product_id' => function () {
                return Product::factory()->create(['user_id' => User::factory()->create()->id])->id;
            },
        ]);

        $this->enableForeignKeys();
    }
}
