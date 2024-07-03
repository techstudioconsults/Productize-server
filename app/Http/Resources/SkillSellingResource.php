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
            'level' => $this->level,
            'availability' => $this->availability,
            'category' => $this->category,
            'link' => $this->link,
            'product' => [
                'id' => $this->product->id,
                'thumbnail' => $this->product->thumbnail,
                'cover_photos' => $this->product->cover_photos,
            ],
            'resources' => $this->resources->map(function ($resource) {
                return [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'url' => $resource->url,
                    'mime_type' => $resource->mime_type,
                    'size' => round($resource->size / 1048576, 3).'MB', // Convert byte to MB
                    'extension' => $resource->extension,
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
