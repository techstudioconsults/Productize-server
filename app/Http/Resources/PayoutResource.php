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
            'bank_name' => $this->payoutAccount->bank_name,
            'account_number' => $this->payoutAccount->account_number,
            'account_name' => $this->payoutAccount->name,
            'amount' => $this->amount,
            'status' => $this->status,
            'reference' => $this->reference,
            'created_at' => $this->created_at
        ];
    }
}
