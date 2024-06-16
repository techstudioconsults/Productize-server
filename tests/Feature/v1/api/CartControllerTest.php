<?php

namespace Tests\Feature;

use App\Exceptions\BadRequestException;
use App\Models\Cart;
use App\Models\User;
use App\Models\Product;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizedException;
use App\Exceptions\UnprocessableException;
use App\Mail\GiftAlert;
use App\Repositories\PaystackRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_index_returns_authenticated_user_carts()
    {
        // Create a user
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        // Create some cart items associated with the user
        $carts = Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $product->slug, // Assuming 'product_slug' is required in your test data
            'quantity' => 1,
        ]);

        // Make a GET request to the carts.index route
        $response = $this->withoutExceptionHandling()->get(route('cart.index'));


        // Assert that the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert that the response contains the correct number of cart items
        $response->assertJsonCount(1);

        // Assert that the response contains the correct data for each cart item

        $response->assertJsonFragment([
            'id' => $carts->id, // Include id in the assertion
            'quantity' => $carts->quantity,
            'product_slug' => $carts->product_slug,
        ]);
    }

    public function test_index_unauthenticated_user_throw_401(): void
    {
        $this->expectException(UnAuthorizedException::class);

        $this->withoutExceptionHandling()->get(route('cart.index'));
    }

    public function test_store_method_creates_cart_item()
    {
        // Create a user
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        // Mock authentication
        $this->actingAs($user);

        // Prepare payload for the request
        $expected_result = [
            'user_id' => $user->id,
            'product_slug' => $product->slug, // Assuming 'product_slug' is required in your test data
            'quantity' => 1,
        ];

        // Make a POST request to the store endpoint
        $response = $this->post(route('cart.store'), $expected_result);

        // Assert that the response status is 201 (created)
        $response->assertCreated();

        // Assert that the response contains the correct data
        $response->assertJson([
            'data' => [
                'product_slug' => $expected_result['product_slug'],
                'quantity' => $expected_result['quantity'],
            ]
        ]);

        // Check if the cart item is actually stored in the database
        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_slug' => $expected_result['product_slug'],
            'quantity' => $expected_result['quantity'],
        ]);
    }

    public function test_store_method_if_inavlid_payload_throw_UnprocessableException(): void
    {
        $this->expectException(UnprocessableException::class);

        // Prevent the exception from being handled automatically
        $this->withoutExceptionHandling();

        // Create a user
        $user = User::factory()->create();

        // Mock authentication
        $this->actingAs($user);

        $payload = [
            'user_id' => $user->id,
            'quantity' => 1, // make the request without a slug
        ];

        // Make a POST request to the store endpoint
        $this->post(route('cart.store'), $payload);
    }

    public function test_store_method_if_cart_exist_throw_ConflictException()
    {
        // Prevent the exception from being handled automatically
        $this->withoutExceptionHandling();

        // Create a user and product
        $user = User::factory()->create();
        $existing_slug = 'example-product';

        // Mock authentication
        $this->actingAs($user);

        // Create an existing cart item for the product
        Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $existing_slug,
            'quantity' => 1
        ]);

        // Prepare payload for the request with the same product
        $payload = [
            'user_id' => $user->id,
            'product_slug' => $existing_slug,
            'quantity' => 1,
        ];

        // Assert that attempting to store a duplicate cart item throws ConflictException
        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('Item exist in cart');

        // Make a POST request to the store endpoint
        $this->post(route('cart.store'), $payload);
    }

    public function test_show_correctly(): void
    {
        // Create a user, product and cart
        $user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $product->slug,
            'quantity' => 1
        ]);

        // Mock authentication
        $this->actingAs($user);

        $response = $this->withoutExceptionHandling()->get(route('cart.show', ['cart' => $cart->id]));

        // Assert that the response status is 200 (OK)
        $response->assertOk();

        // Assert that the response contains the updated cart data
        $response->assertJson([
            'data' => [
                'id' => $cart->id,
                'quantity' => $cart->quantity,
                'product_slug' => $product->slug
            ]
        ]);
    }

    public function test_show_unauthenticated_user_throws_UnAuthorizedException(): void
    {
        $this->expectException(UnAuthorizedException::class);

        // Create a user, product and cart
        $user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $product->slug,
            'quantity' => 1
        ]);

        $this->withoutExceptionHandling()->get(route('cart.show', ['cart' => $cart->id]));
    }

    public function test_show_method_invalid_cart_id_throw_ModelNotFoundException(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $cart_id = "invalid_cart_id";

        $user = User::factory()->create();

        // Mock authentication
        $this->actingAs($user);

        $this->withoutExceptionHandling()->get(route('cart.show', ['cart' => $cart_id]));
    }

    public function test_show_forbidden_user_throws_ForbiddenException(): void
    {
        $this->expectException(ForbiddenException::class);

        // Create a user, product and cart
        $user = User::factory()->create();

        $forbidden_user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $product->slug,
            'quantity' => 1
        ]);

        // Mock authentication
        $this->actingAs($forbidden_user);

        $this->withoutExceptionHandling()->get(route('cart.show', ['cart' => $cart->id]));
    }

    public function test_update_method_updates_cart_correctly()
    {
        // Create a user
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        // Mock authentication
        $this->actingAs($user);

        // Create a cart for the user
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $product->slug,
            'quantity' => 1
        ]);

        // Define the payload with the updated quantity
        $payload = [
            'quantity' => 5, // Update the quantity to 5 or any desired value
        ];

        // Make a PUT request to the update endpoint with the payload
        $response = $this->patch(route('cart.update', ['cart' => $cart->id]), $payload);

        // Assert that the response status is 200 (OK)
        $response->assertOk();

        // Assert that the response contains the updated cart data
        $response->assertJson([
            'data' => [
                'id' => $cart->id,
                'quantity' => $payload['quantity']
            ]
        ]);
    }

    public function test_update_method_unauthenticated_user_throw_UnAuthorizedException(): void
    {
        $this->expectException(UnAuthorizedException::class);

        // Create a user, product and cart
        $user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $product->slug,
            'quantity' => 1
        ]);

        $payload = [
            'quantity' => 1,
        ];

        $this->withoutExceptionHandling()->patch(route('cart.update', ['cart' => $cart->id]), $payload);
    }

    public function test_update_method_forbidden_user_throw_ForbiddenException(): void
    {
        $this->expectException(ForbiddenException::class);

        // Create a user, product and cart
        $user = User::factory()->create();

        $forbidden_user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $product->slug,
            'quantity' => 1
        ]);

        $payload = [
            'quantity' => 2,
        ];

        // Mock authentication
        $this->actingAs($forbidden_user);

        $this->withoutExceptionHandling()->patch(route('cart.update', ['cart' => $cart->id]), $payload);
    }

    public function test_update_method_invalid_cart_id_throw_ModelNotFoundException(): void
    {
        $this->expectException(ModelNotFoundException::class);

        // Create a user, product and cart
        $user = User::factory()->create();

        $cart_id = "invalid_cart_id";

        $payload = [
            'quantity' => 2,
        ];

        // Mock authentication
        $this->actingAs($user);

        $this->withoutExceptionHandling()->patch(route('cart.update', ['cart' => $cart_id]), $payload);
    }

    public function test_update_method_invalid_payload_throw_UnprocessableEntityException(): void
    {
        $this->expectException(UnprocessableException::class);

        // Create a user, product and cart
        $user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $product->slug,
            'quantity' => 1
        ]);

        $payload = [
            'product_slug' => "updated slug", // Attempt to update the product slug
        ];

        // Mock authentication
        $this->actingAs($user);

        $this->withoutExceptionHandling()->patch(route('cart.update', ['cart' => $cart->id]), $payload);
    }

    public function test_delete_method_deletes_cart_correctly()
    {
        // Create a user
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        // Mock authentication
        $this->actingAs($user);


        // Create a cart instance
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $product->slug, // Assuming 'product_slug' is required in your test data
            'quantity' => 1,
        ]);

        // Make a DELETE request to the delete endpoint
        $response = $this->delete("/api/carts/{$cart->id}");

        // Assert that the response status code is 200
        $response->assertStatus(200);

        // Assert that the cart has been deleted from the database
        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
    }

    public function test_delete_method_unauthenticated_user_throw_UnAuthorizedException(): void
    {
        $this->expectException(UnAuthorizedException::class);

        // Create a user, product and cart
        $user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $product->slug,
            'quantity' => 1
        ]);

        $this->withoutExceptionHandling()->delete(route('cart.delete', ['cart' => $cart->id]));
    }

    public function test_delete_method_forbidden_user_throw_ForbiddenException(): void
    {
        $this->expectException(ForbiddenException::class);

        // Create a user, product and cart
        $user = User::factory()->create();

        $forbidden_user = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'product_slug' => $product->slug,
            'quantity' => 1
        ]);
        // Mock authentication
        $this->actingAs($forbidden_user);

        $this->withoutExceptionHandling()->delete(route('cart.delete', ['cart' => $cart->id]));
    }

    public function test_delete_method_invalid_cart_id_throw_ModelNotFoundExeption(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $cart_id = "invalid_cart_id";

        $user = User::factory()->create();

        // Mock authentication
        $this->actingAs($user);

        $this->withoutExceptionHandling()->delete(route('cart.delete', ['cart' => $cart_id]));
    }

    public function test_clear_cart_without_gift_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['price' => 1000, 'status' => 'published']);

        $productRepository = $this->partialMock(ProductRepository::class);

        $productRepository->shouldReceive('findOne')
            ->once()
            ->with(['slug' => $product->slug])
            ->andReturn($product);

        $paystackRepository = $this->partialMock(PaystackRepository::class);

        $paystackRepository->shouldReceive('initializePurchaseTransaction')
            ->once()
            ->andReturn(['status' => 'success', 'data' => []]);

        $response = $this->withoutExceptionHandling()->post(route('cart.clear'), [
            'products' => [
                ['product_slug' => $product->slug, 'quantity' => 1]
            ],
            'amount' => 1000
        ]);

        $response->assertStatus(200);
    }

    public function test_clear_cart_with_existing_gift_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient_email = 'gift@example.com';
        $recipient_name = 'Gift User';

        User::factory()->create(['email' => $recipient_email]);
        $product = Product::factory()->create(['price' => 1000, 'status' => 'published']);

        $userRepository = $this->partialMock(UserRepository::class);

        $userRepository->shouldReceive('query')
            ->once()
            ->with(['email' => $recipient_email])
            ->andReturn(User::query()->where('email', $recipient_email));

        $productRepository = $this->partialMock(ProductRepository::class);

        $productRepository->shouldReceive('findOne')
            ->once()
            ->with(['slug' => $product->slug])
            ->andReturn($product);

        $paystackRepository = $this->partialMock(PaystackRepository::class);

        $paystackRepository->shouldReceive('initializePurchaseTransaction')
            ->once()
            ->andReturn(['status' => 'success', 'data' => []]);

        $response = $this->post(route('cart.clear'), [
            'recipient_email' => $recipient_email,
            'recipient_name' => $recipient_name,
            'products' => [
                ['product_slug' => $product->slug, 'quantity' => 1]
            ],
            'amount' => 1000
        ]);

        $response->assertStatus(200);
    }

    public function test_clear_cart_creates_new_gift_user_and_sends_email()
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient_email = 'gift@example.com';
        $recipient_name = 'Gift User';

        $gift_user = User::factory()->make(['email' => $recipient_email, 'full_name' => $recipient_name]);

        $product = Product::factory()->create(['price' => 1000, 'status' => 'published']);

        $userRepository = $this->partialMock(UserRepository::class);

        $userRepository->shouldReceive('query')
            ->once()
            ->with(['email' => $recipient_email])
            ->andReturn(User::query()->where('email', $recipient_email));

        $userRepository->shouldReceive('create')
            ->once()
            ->with(['email' => $recipient_email, 'full_name' => $recipient_name])
            ->andReturn($gift_user);

        $productRepository = $this->partialMock(ProductRepository::class);

        $productRepository->shouldReceive('findOne')
            ->once()
            ->with(['slug' => $product->slug])
            ->andReturn($product);

        $paystackRepository = $this->partialMock(PaystackRepository::class);

        $paystackRepository->shouldReceive('initializePurchaseTransaction')
            ->once()
            ->andReturn(['status' => 'success', 'data' => []]);

        $response = $this->withoutExceptionHandling()->post(route('cart.clear'), [
            'recipient_email' => $recipient_email,
            'recipient_name' => $recipient_name,
            'products' => [
                ['product_slug' => $product->slug, 'quantity' => 1]
            ],
            'amount' => $product->price
        ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['status' => 'success']]);

        Mail::assertSent(GiftAlert::class, function ($mail) use ($recipient_email) {
            return $mail->hasTo($recipient_email);
        });
    }

    public function test_clear_cart_throws_error_if_product_not_found()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $slug = 'non-existent-product';

        $productRepository = $this->partialMock(ProductRepository::class);

        $productRepository->shouldReceive('findOne')
            ->once()
            ->with(['slug' => $slug])
            ->andReturn(null);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Product with slug non-existent-product not found');

        $this->withoutExceptionHandling()->post(route('cart.clear'), [
            'products' => [
                ['product_slug' => $slug, 'quantity' => 1]
            ],
            'amount' => 1000
        ]);
    }

    public function test_clear_cart_throws_error_if_total_amount_mismatch()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['price' => 1000, 'status' => 'published']);

        $productRepository = $this->partialMock(ProductRepository::class);

        $productRepository->shouldReceive('findOne')
            ->once()
            ->with(['slug' => $product->slug])
            ->andReturn($product);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Total amount does not match quantity');

        $this->withoutExceptionHandling()->post(route('cart.clear'), [
            'products' => [
                ['product_slug' => $product->slug, 'quantity' => 1]
            ],
            'amount' => 2000 // Intentionally mismatched amount
        ]);
    }
}
