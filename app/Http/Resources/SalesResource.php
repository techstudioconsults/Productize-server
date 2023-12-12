<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_title' => $this->product->title,
            'product_price' => $this->product->price,
            'quantity' => $this->quantity,
            'customer_name' => $this->customer->full_name,
            'customer_email' => $this->customer->email,
            'date' => $this->created_at
        ];
    }
}
