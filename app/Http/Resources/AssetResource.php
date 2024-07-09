<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
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
            'name' => $this->name,
            'mime_type' => $this->mime_type,
            'size' => round($this->size / 1048576, 3).'MB', // Convert byte to MB
            'extension' => $this->extension,
            'url' => $this->url,
            'publisher' => $this->product->user->full_name,
        ];
    }
}
