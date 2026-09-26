<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrivateMessageResource extends JsonResource
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
            'private_conversation_id' => $this->private_conversation_id,
            'user_id' => $this->user_id,
            'content' => $this->content,
            'author' => $this->whenLoaded('user', fn(): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'can_edit' => $request->user()?->id === $this->user_id,
        ];
    }
}
