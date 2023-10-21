<?php

namespace Database\Seeders;

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
            Product::factory(20)->create();
            $this->enableForeignKeys();
    }
}
