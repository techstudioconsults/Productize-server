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
            'product' => [
                'id' => $this->product->id,
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
        ];
    }
}
