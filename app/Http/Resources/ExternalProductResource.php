<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'thumbnail' => $this->thumbnail,
            'price' => (int) $this->price,
            'discount_price' => (int) $this->discount_price,
            'publisher' => $this->user->full_name,
            'slug' => $this->slug,
            'highlights' => $this->highlights,
            'product_type' => $this->product_type,
            'cover_photos' => $this->cover_photos,
            'tags' => $this->tags,
            'description' => $this->description,
            'status' => $this->status,
            'assets' => $this->assets->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'mime_type' => $asset->mime_type,
                    'size' => round($asset->size / 1048576, 3) . 'MB', // Convert byte to MB
                    'extension' => $asset->extension,
                ];
            }),
            'avg_rating' => (int) $this->reviews()->avg('rating'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
