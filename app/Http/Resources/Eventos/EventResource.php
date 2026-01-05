<?php

namespace App\Http\Resources\Eventos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
            'is_all_day' => $this->is_all_day,
            'location' => $this->location,
            'status' => $this->status,
            'category' => new EventCategoryResource($this->whenLoaded('category')),
            'color' => $this->category ? $this->category->color : null,
            'text_color' => $this->category ? $this->category->text_color : null,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
