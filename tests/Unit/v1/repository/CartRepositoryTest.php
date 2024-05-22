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

    public function test_create_invalid_payload(): void
    {

    }

    public function test_find(): void
    {

    }

    public function test_find_with_user_id(): void
    {

    }

    public function test_find_with_date_range(): void
    {

    }

    public function test_find_with_wrong_user_id_returns_empty_array(): void
    {

    }

    public function test_findbyrelation_with_user(): void
    {

    }

    public function test_findbyid(): void
    {

    }

    public function test_findbyid_wrong_id_return_null(): void
    {

    }

    public function test_findone_with_slug(): void
    {

    }

    public function test_findone_with_wrong_slug_return_null(): void
    {

    }

    public function test_update(): void
    {

    }

    public function test_update_cart_model_not_passed_throw_ModelCastException(): void
    {

    }
}
