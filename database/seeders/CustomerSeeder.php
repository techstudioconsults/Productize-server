<?php

namespace Database\Seeders;

use App\Models\Customer;
use Database\Factories\ProductFactory;
use Database\Seeders\Traits\DisableForeignKeys;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    use TruncateTable, DisableForeignKeys;

    public function run(): void
    {
        $this->disableForeignKeys();
        $this->truncate('customers');
        // Customer::factory(10)->create();
        $this->enableForeignKeys();
    }
}
