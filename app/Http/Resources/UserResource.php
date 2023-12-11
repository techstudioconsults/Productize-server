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
            'account_type' => $this->account_type,
            'logo' => $this->logo,
            'twitter_account' => $this->twitter_account,
            'facebook_account' => $this->facebook_account,
            'youtube_account' => $this->youtube_account,
            'email_verified' => (bool) $this->hasVerifiedEmail(),
            'profile_completed' => (bool) $this->profile_completed_at,
            'first_product_created' => (bool) $this->first_product_created_at,
            'payout_setup' => (bool) $this->payout_setup_at,
            'first_sale' => (bool) $this->first_sale_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
