<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Policies\PrivateConversationPolicy;

class GroupResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'content' => $this->content_html,
            'footer' => $this->footer_html,
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i'),
            'private_message_url' => $this->canStartPrivateConversation($request)
                ? route('private-conversations.store')
                : null,
        ];
    }

    private function canStartPrivateConversation(Request $request): bool
    {
        return $request->user() !== null
            && (new PrivateConversationPolicy)->createForGroup($request->user(), $this->resource);
    }
}
