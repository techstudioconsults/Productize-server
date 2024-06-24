<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        $merchant = User::factory()->create();

        return [
            'user_id' => $user->id,
            'order_id' => Order::factory()->create([
                'user_id' => $user->id,
                'product_id' => Product::factory()->create(['user_id' => $merchant->id]),
            ])->id,
            'merchant_id' => $merchant->id,
        ];
    }
}
