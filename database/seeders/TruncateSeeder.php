<?php

namespace Database\Seeders;

use Database\Seeders\Traits\DisableForeignKeys;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;

class TruncateSeeder extends Seeder
{
    use DisableForeignKeys, TruncateTable;

    public function run(): void
    {
        $this->disableForeignKeys();
        $this->truncate('users');
        $this->truncate('products');
        $this->truncate('orders');
        $this->truncate('payouts');
        $this->truncate('reviews');
        $this->truncate('users');
        $this->enableForeignKeys();
    }
}
