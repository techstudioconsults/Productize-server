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
            ],
            'created_at' => $this->created_at,
        ];
    }
}
