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
            'alt_email' => $this->alt_email,
            'email_verified' => (bool) $this->hasVerifiedEmail(),
            'profile_completed' => (bool) $this->profile_completed_at,
            'first_product_created' => (bool) $this->first_product_created_at,
            'payout_setup' => (bool) $this->hasPayoutSetup(),
            'first_sale' => (bool) $this->firstSale(),
            'product_creation_notification' => (bool) $this->product_creation_notification,
            'purchase_notification' => (bool) $this->purchase_notification,
            'news_and_update_notification' => (bool) $this->news_and_update_notification,
            'payout_notification' => (bool) $this->payout_notification,
            'country' => $this->country,
            'document_type' => $this->document_type,
            'document' => $this->document,
            'kyc_complete' => (bool) ($this->country && $this->document_type && $this->document),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
