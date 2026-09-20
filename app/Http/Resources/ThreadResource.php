<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThreadResource extends JsonResource
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
            'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'visibility' => $this->visibility,
            'is_locked' => $this->is_locked,
            'author' => $this->whenLoaded('user', fn(): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'comments_count' => $this->when(
                isset($this->comments_count),
                $this->comments_count,
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
