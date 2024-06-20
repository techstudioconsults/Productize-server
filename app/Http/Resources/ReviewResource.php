<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 22-05-2024
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
        'user' => [
          'id' =>  $this->user->id,
          'name' => $this->user->full_name,
          'logo' => $this->user->logo,
        ],
        'product'=>$this->product_id,
        'rating' => $this->rating,
        'comment' => $this->comment,
        'created_at' => $this->created_at,
       ];
    }
}
