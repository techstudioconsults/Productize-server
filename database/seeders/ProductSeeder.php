<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use Database\Seeders\Traits\DisableForeignKeys;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use TruncateTable, DisableForeignKeys;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->disableForeignKeys();
        $this->truncate('products');
        Product::factory(50)->create()->each(function ($product) {
            Customer::factory(10)->create(['product_id' => $product->id]);
        });
        $this->enableForeignKeys();
    }
}
