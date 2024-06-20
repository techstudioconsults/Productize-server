<?php

namespace Database\Seeders;

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
        Product::factory(10)->create(['user_id' => User::factory()->create()->id]);
        $this->enableForeignKeys();
    }
}
