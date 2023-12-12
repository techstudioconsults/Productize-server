<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
            'email' => $this->order->buyer->email,
            'product_title' => $this->product->title,
            'product_price' => $this->product->price,
            'total_amount' => $this->total_amount,
            'total_order' => $this->product->orders->count(),
            'total_sales' => $this->product->orders->sum('total_amount'),
            'total_views' => 1,
            'created_at' => $this->created_at
        ];
    }
}
