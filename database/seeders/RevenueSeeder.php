<?php

namespace Database\Seeders;

use App\Enums\RevenueActivity;
use App\Models\Revenue;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RevenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Revenue::factory()
            ->count(10)
            ->sequence(
                ['activity' => RevenueActivity::PURCHASE->value, 'product' => 'Purchase'],
                ['activity' => RevenueActivity::SUBSCRIPTION->value, 'product' => 'Subscription'],
                ['activity' => RevenueActivity::SUBSCRIPTION_RENEW->value, 'product' => 'Subscription'],
            )
            ->create();
    }
}
