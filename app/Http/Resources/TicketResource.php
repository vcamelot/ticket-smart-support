<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string)$this->status,
            'category' => $this->category,
            'sentiment' => $this->sentiment,
            'urgency' => $this->urgency,
            'suggested_reply' => $this->suggested_reply,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
