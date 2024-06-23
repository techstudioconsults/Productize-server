<?php

namespace Database\Seeders;

use App\Enums\ProductStatusEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{

    public function run(): void
    {
        Product::factory(5)
            ->sequence(
                ['status' => ProductStatusEnum::Draft->value],
                ['status' => ProductStatusEnum::Published->value],
            )
            ->create(['user_id' => User::factory()->create()->id]);

        Product::factory()->count(5)->has(Order::factory()->count(5), 'orders')->create([
            'user_id' => User::where('email', 'tobiolanitori1@gmail.com')->first()->id ?? User::factory()->create()->id,
            'status' => ProductStatusEnum::Published->value
        ]);
    }
}
