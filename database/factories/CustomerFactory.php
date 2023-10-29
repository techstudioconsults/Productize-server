<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
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
        return [
            'name' => fake()->unique()->name(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Customer $customer) {
            Order::factory(5)->create(
                [
                    'product_id' => $customer->product_id,
                    'customer_id' => $customer->id
                ]
            );
        });
    }
}
