<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_number' => fake()->numerify('#########'),
            'paystack_recipient_code' => 'RCP_2x5j67tnnw1t98k',
            'name' => fake()->name(),
            'bank_code' => "033",
            'bank_name' => "United Bank of Africa",
            'user_id' => User::factory()->create()->id,
            'active' => 1
        ];
    }
}
