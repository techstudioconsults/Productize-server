<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
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
            'bank_name' => $this->account->bank_name,
            'amount' => $this->amount,
            'status' => $this->status,
            'reference' => $this->reference,
            'created_at' => $this->created_at,
            'account' => [
                'name' => $this->account->name,
                'number' => $this->account->account_number,
            ],
        ];
    }
}
