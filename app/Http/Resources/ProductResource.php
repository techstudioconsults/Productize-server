<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'user_id' => $this->user_id,
            'title' => $this->title,
            'logo' => $this->user->logo,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'description' => $this->description,
            'product_type' => $this->product_type,
            'highlights' => $this->highlights,
            'thumbnail' => $this->thumbnail,
            'cover_photos' => $this->cover_photos,
            'tags' => $this->tags,
            'stock_count' => (bool) $this->stock_count,
            'choose_quantity' => (bool) $this->choose_quantity,
            'show_sales_count' => (bool) $this->show_sales_count,
            'link' => config('app.client_url')."/products/$this->slug",
            'status' => $this->status ?? 'draft',
            'slug' => $this->slug,
            'total_order' => $this->totalOrder(),
            'total_sales' => (int) $this->totalSales(),
            'assets' => $this->assets->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'mime_type' => $asset->mime_type,
                    'size' => round($asset->size / 1048576, 3).'MB', // Convert byte to MB
                    'extension' => $asset->extension,
                ];
            }),
            'avg_rating' => (int) $this->reviews()->avg('rating'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
