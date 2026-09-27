<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrivateConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $participant = $this->whenLoaded('participants', fn() => $this->participants
            ->firstWhere('user_id', $request->user()?->id));

        return [
            'id' => $this->id,
            'blog_id' => $this->blog_id,
            'group_id' => $this->group_id,
            'post_id' => $this->post_id,
            'subject' => $this->subject,
            'initiator' => $this->whenLoaded('initiator', fn(): array => [
                'id' => $this->initiator->id,
                'name' => $this->initiator->name,
            ]),
            'owner' => $this->whenLoaded('owner', fn(): array => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'messages_count' => $this->when(isset($this->messages_count), $this->messages_count),
            'messages' => PrivateMessageResource::collection($this->whenLoaded('messages')),
            'source' => $this->source(),
            'email_notifications' => $participant?->email_notifications,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array{type: string, label: string, url: string}|null
     */
    private function source(): ?array
    {
        if ($this->relationLoaded('post') && $this->post !== null) {
            return [
                'type' => 'post',
                'label' => $this->post->title,
                'url' => $this->post->public_url,
            ];
        }

        if ($this->blog_id !== null && $this->relationLoaded('blog') && $this->blog !== null) {
            return [
                'type' => 'blog',
                'label' => $this->blog->name,
                'url' => $this->blog->public_url,
            ];
        }

        if ($this->group_id !== null && $this->relationLoaded('group') && $this->group !== null) {
            return [
                'type' => 'group',
                'label' => $this->group->name,
                'url' => route('group.landing', $this->group),
            ];
        }

        return null;
    }
}
