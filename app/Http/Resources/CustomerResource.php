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

        $total_purchase = $this->user->purchases()
            ->whereHas('product', function ($query) {
                $query->where('user_id', $this->merchant_id);
            });

        $total_purchase_amount = $total_purchase
            ->with(['product' => function ($query) {
                $query->select('id', 'price');
            }])
            ->get()
            ->sum(function ($purchase) {
                return $purchase->product->price * $purchase->quantity;
            });


        // name, email, latest purchase, price, date (last product purchase date - updated at), joined(created at), total order, total_transaction
        return [
            'id' => $this->id,
            'name' => $this->user->full_name,
            'email' => $this->user->email,
            'free_products' => 5,
            'sale_products' => 5,
            'total_order' => $total_purchase->count(),
            'total_transactions' => $total_purchase_amount,
            'latest_purchase_title' => $this->order->product->title,
            'latest_purchase_price' => $this->order->product->price,
            'latest_purchase_date' => $this->order->updated_at,
            'joined' => $total_purchase->first()->created_at,
            'latest_purchases' => OrderResource::collection($total_purchase->orderBy('created_at', 'desc')
                ->take(3)
                ->get())
        ];
    }
}
