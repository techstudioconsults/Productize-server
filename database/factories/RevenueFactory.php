<?php

namespace Database\Factories;

use App\Enums\RevenueActivity;
use App\Enums\RevenueActivityStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Revenue>
 */
class RevenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity' => RevenueActivity::PURCHASE->value,
            'product' => 'Purchase',
            'amount' => fake()->randomNumber(5),
            'user_id' => User::factory()->create()->id,
            'status' => RevenueActivityStatus::PENDING->value,
        ];
    }
}
