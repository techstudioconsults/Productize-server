<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
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
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
            'user' => [
                'name' => $this->user->full_name,
                'email' => $this->user->email,
                'avatar' => $this->user->logo
            ],
            'created_at' => $this->created_at
        ];
    }
}
