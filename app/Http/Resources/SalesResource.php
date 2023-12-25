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
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'product_price' => $this->product->price,
            'product_thumbnail' => $this->product->thumbnail,
            'product_publish_date' => $this->product->created_at,
            'quantity' => $this->quantity,
            'total_amount' => $this->total_amount,
            'customer_name' => $this->customer->full_name,
            'customer_email' => $this->customer->email,
            'total_order' => $this->product->sales->count(),
            'total_sales' => $this->product->sales->sum('total_amount'),
            'total_views' => 1,
            'bank_name' => $this->subaccount->bank_name,
            'bank_account_number' => $this->subaccount->account_number,
            'date' => $this->created_at
        ];
    }
}
