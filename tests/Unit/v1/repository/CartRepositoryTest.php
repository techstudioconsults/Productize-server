<?php

namespace Tests\Unit\v1\repository;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Repositories\CartRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CartRepository $cartRepository;

    public function test_create(): void
    {
        // user_id, product_slug, quantity

        $user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $expected_result = [
            'user_id' => $user->id,
            'product_slug' => $product->slug,
            'quantity' => 3
        ];

        $result = $this->cartRepository->create($expected_result);

        // Assert
        $this->assertInstanceOf(Cart::class, $result);

        $this->assertEquals($expected_result['user_id'], $result->user_id);
        $this->assertEquals($expected_result['product_slug'], $result->product_slug);
        $this->assertEquals($expected_result['quantity'], $result->quantity);
    }
}
