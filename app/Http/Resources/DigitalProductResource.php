<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalProductResource extends JsonResource
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
            'product' => [
                'id' => $this->product->id,
                'thumbnail' => $this->product->thumbnail,
                'cover_photos' => $this->product->cover_photos,
            ],
            'assets' => $this->assets->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'url' => $asset->url,
                    'mime_type' => $asset->mime_type,
                    'size' => round($asset->size / 1048576, 3).'MB', // Convert byte to MB
                    'extension' => $asset->extension,
                ];
            }),
        ];
    }
}
