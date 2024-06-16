<?php

namespace Database\Seeders;

use Database\Seeders\Traits\DisableForeignKeys;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    use DisableForeignKeys, TruncateTable;

    public function run(): void
    {
        $this->disableForeignKeys();
        $this->truncate('orders');
        $this->enableForeignKeys();
    }
}
