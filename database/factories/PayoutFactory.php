<?php

namespace Database\Factories;

use App\Enums\PayoutStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payout>
 */
class PayoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => Str::random(10),
            'status' => PayoutStatusEnum::Completed->value,
            'paystack_transfer_code' => 'TRF_1ptvuv321ahaa7q',
            'amount' => '20000',
        ];
    }
}
