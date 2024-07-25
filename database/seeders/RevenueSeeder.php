<?php

namespace Database\Seeders;

use App\Enums\RevenueActivity;
use App\Enums\RevenueActivityStatus;
use App\Models\Revenue;
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
                ['status' => RevenueActivityStatus::COMPLETED->value],
                ['status' => RevenueActivityStatus::PENDING->value],
                ['status' => RevenueActivityStatus::FAILED->value]

            )
            ->create();
    }
}
