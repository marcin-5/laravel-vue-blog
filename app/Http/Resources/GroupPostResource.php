<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupPostResource extends JsonResource
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
            'slug' => $this->slug,
            'author' => $this->user?->name ?? $this->group?->user?->name,
            'author_email' => $this->user?->email ?? $this->group?->user?->email,
            'summaryHtml' => $this->summary_html,
            'contentHtml' => $this->content_html,
            'published_at' => $this->published_at?->format('Y-m-d H:i'),
            'excerpt' => $this->excerpt,
            'summary' => $this->summary,
            'extensions' => $this->extensions
                ->where('is_published', true)
                ->map(fn($ext) => [
                    'id' => $ext->id,
                    'title' => $ext->title,
                    'contentHtml' => $ext->content_html,
                ])->values(),
        ];
    }
}
