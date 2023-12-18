<?php

namespace App\Http\Resources;

use App\Models\Product;
use Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'total_amount' => $this->total_amount,
            'products' => $this->getProduct($this->products),
        ];
    }

    private function getProduct($carts)
    {
        $cartsArray = $carts->getArrayCopy();

        $products = Arr::map($cartsArray, function ($cart) {
            $product = Product::firstWhere('slug', $cart['product_slug']);

            return [
                'title' => $product->title,
                'thumbnail' => $product->thumbnail,
                'price' => $product->price,
                'publisher' => $product->user->full_name,
                'slug' => $product->slug,
                'highlights' => $product->highlights,
                'product_type' => $product->product_type,
                'cover_photos' => $product->cover_photos,
                'tags' => $product->tags,
                'description' => $product->description,
                'quantity' => $cart['quantity']
            ];
        });

        return $products;
    }
}
