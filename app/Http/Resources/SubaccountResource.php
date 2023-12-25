<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubaccountResource extends JsonResource
{
    protected $extraData;

    public function __construct($resource, $extraData = [])
    {
        parent::__construct($resource);
        $this->extraData = $extraData;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge([
            'id' => $this->id,
        ], $this->extraData);
    }
}
