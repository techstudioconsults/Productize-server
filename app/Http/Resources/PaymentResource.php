<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'user_id' => $this->user->id,
            'total_earnings' => $this->total_earnings,
            'withdrawn_earnings' => $this->withdrawn_earnings,
            'available_earnings' => $this->total_earnings - $this->withdrawn_earnings,
            'pending' => $this->pending,

        ];
    }
}
