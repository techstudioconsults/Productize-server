<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'reference_no' => $this->reference_no,
            'product_thumbnail' => $this->product->thumbnail,
            'product_title' => $this->product->title,
            'product_price' => $this->product->price,
            'customer_email' => $this->user->email,
            'total_orders' => $this->product->totalOrder(),
            'total_sales' => $this->product->totalSales(),
            'total_amount' => $this->product->price * $this->quantity,
            'quantity' => $this->quantity,
            'product_publish_date' => $this->product->created_at,
            'link' => config('app.client_url') . "/products/".$this->product->slug,
            'created_at' => $this->created_at
        ];
    }
}
