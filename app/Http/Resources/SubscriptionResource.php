<?php

namespace App\Http\Resources;

use App\Repositories\SubscriptionRepository;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
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
            'status' => $this->status,
            'price' => SubscriptionRepository::PRICE,
            'user' => [
                'id' => $this->user->id,
                'email' => $this->user->email,
                'full_name' => $this->user->full_name
            ]
        ];
    }
}
