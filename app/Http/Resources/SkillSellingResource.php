<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkillSellingResource extends JsonResource
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
            'category' => $this->category,
            'link' => $this->link,
            'resource_link' => $this->resource_link, 
            'product' => [
                'id' => $this->product->id,
                'thumbnail' => $this->product->thumbnail,
                'cover_photos' => $this->product->cover_photos,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
