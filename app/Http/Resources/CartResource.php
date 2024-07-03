<?php

namespace App\Http\Resources;

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
            'quantity' => $this->quantity,
            'product_slug' => $this->product->slug,
            'product_title' => $this->product->title,
            'product_thumbnail' => $this->product->thumbnail,
            'product_price' => $this->product->discount_price > 0 ? $this->product->discount_price : $this->product->price,
        ];
    }
}
