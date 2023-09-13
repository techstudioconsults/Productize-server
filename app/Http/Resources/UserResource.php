<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->full_name,
            'username' => $this->username,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'bio' => $this->bio,
            'email_verified' => $this->email_verified_at ? true : false,
            'account_type' => $this->account_type,
            'logo' => $this->logo,
            'twitter_account' => $this->twitter_account,
            'facebook_account' => $this->facebook_account,
            'youtube_account' => $this->youtube_account,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
