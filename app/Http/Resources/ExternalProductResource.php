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
            'resources' => $this->resources->map(function ($resource) {
                return [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'mime_type' => $resource->mime_type,
                    'size' => round($resource->size / 1048576, 3).'MB', // Convert byte to MB
                    'extension' => $resource->extension,
                ];
            }),
            'avg_rating' => (int) $this->reviews()->avg('rating'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
