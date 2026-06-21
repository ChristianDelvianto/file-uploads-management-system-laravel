<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->plan->id, // Easy to check on frontend
            'name' => $this->plan->name,
            'limit_bytes' => $this->plan->limit_bytes,
        ];
    }
}
