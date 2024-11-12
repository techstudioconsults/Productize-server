<?php

namespace App\Http\Resources;

use App\Enums\ProductStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FunnelResource extends JsonResource
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
            'title' => $this->title,
            'status' => $this->status,
            'thumbnail' => $this->thumbnail,
            'slug' => $this->slug,
            'url' => $this->when($this->status === ProductStatusEnum::Published->value, "https://{$this->slug}.trybytealley.com"),
            'created_at' => $this->created_at,
        ];
    }
}
