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
            'id' => $this->id,
            'product_title' => $this->product->title,
            'product_price' => $this->product->price,
            'product_thumbnail' => $this->product->thumbnail,
            'quantity' => $this->quantity,
            'total_amount' => $this->total_amount,
            'customer_name' => $this->customer->full_name,
            'customer_email' => $this->customer->email,
            'total_order' => $this->product->sales->count(),
            'total_sales' => $this->product->sales->sum('total_amount'),
            'total_views' => 1,
            'date' => $this->created_at
        ];
    }
}
