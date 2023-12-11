<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'name' => $this->user->full_name,
            'email' => $this->user->email,
            'product_title' => $this->product->title,
            'product_price' => $this->product->price,
            'free_products' => 5,
            'sale_products' => 5,
            'total_orders' => 5,
            'total_transactions' => 5,
            'created_at' => $this->created_at
        ];
    }
}
