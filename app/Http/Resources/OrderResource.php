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
            'quantity' => $this->quantity,
            'total_amount' => $this->product->price * $this->quantity,
            'product' => [
                'title' => $this->product->title,
                'price' => $this->product->price,
                'discount_price'=> $this->product->discount_price,
                'thumbnail' => $this->product->thumbnail,
                'total_orders' => $this->product->totalOrder(),
                'total_sales' => $this->product->totalSales(),
                'publish_date' => $this->product->created_at,
                'link' => config('app.client_url').'/products/'.$this->product->slug,
            ],
            'customer' => [
                'name' => $this->user->full_name,
                'email' => $this->user->email,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
