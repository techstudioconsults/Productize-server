<?php

namespace Database\Seeders;

use App\Enums\ProductStatusEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\Traits\DisableForeignKeys;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use DisableForeignKeys, TruncateTable;

    public function run(): void
    {
        $this->disableForeignKeys();
        $this->truncate('products');
        Product::factory(10)
            ->sequence(
                ['status' => ProductStatusEnum::Draft->value],
                ['status' => ProductStatusEnum::Published->value],
            )
            ->create(['user_id' => User::factory()->create()->id]);

        Product::factory()->count(5)->has(Order::factory()->count(5), 'orders')->create([
            'user_id' => User::where('email', 'tobiolanitori1@gmail.com')->first()->id,
            'status' => ProductStatusEnum::Published->value
        ]);


        $this->enableForeignKeys();
    }
}
