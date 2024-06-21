<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\Traits\DisableForeignKeys;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    use DisableForeignKeys, TruncateTable;

    public function run(): void
    {
        Order::factory(10)->create([
            'product_id' => Product::factory()->create(['user_id' => User::factory()->create()->id])->id,
        ]);
    }
}
