<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicPostDetailResource extends JsonResource
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
            'author' => $this->user?->name ?? $this->blog->user->name,
            'author_email' => $this->user?->email ?? $this->blog->user->email,
            'summaryHtml' => $this->summary_html,
            'contentHtml' => $this->content_html,
            'published_at' => $this->published_at?->format('Y-m-d'),
            'visibility' => $this->visibility,
            'excerpt' => $this->excerpt,
            'summary' => $this->summary,
            'extensions' => $this->extensions->map(fn($ext) => [
                'id' => $ext->id,
                'title' => $ext->title,
                'contentHtml' => $ext->content_html,
            ]),
            'relatedPosts' => $this->relatedPosts->map(fn($rp) => [
                'id' => $rp->id,
                'blog_id' => $rp->blog_id,
                'related_post_id' => $rp->related_post_id,
                'reason' => $rp->reason,
                'excerpt' => $rp->relatedPost?->excerpt,
                'display_order' => $rp->display_order,
                'blog_slug' => $rp->relatedPost?->blog?->slug,
                'related_post' => $rp->relatedPost ? [
                    'id' => $rp->relatedPost->id,
                    'title' => $rp->relatedPost->title,
                    'slug' => $rp->relatedPost->slug,
                ] : null,
            ]),
            'externalLinks' => $this->externalLinks->map(fn($el) => [
                'id' => $el->id,
                'title' => $el->title,
                'url' => $el->url,
                'description' => $el->description,
                'reason' => $el->reason,
                'display_order' => $el->display_order,
            ]),
        ];
    }
}
