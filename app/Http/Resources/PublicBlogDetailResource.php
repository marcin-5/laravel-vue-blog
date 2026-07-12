<?php

namespace App\Http\Resources;

use App\Services\MarkdownService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicBlogDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var MarkdownService $markdown */
        $markdown = app(MarkdownService::class);
        $descriptionHtml = str_replace('-!-', '', $markdown->convertToHtml($this->description));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'main_domain' => $this->main_domain,
            'url' => $this->public_url,
            'motto' => $this->motto,
            'theme' => $this->theme,
            'descriptionHtml' => $descriptionHtml,
            'aboutHtml' => $markdown->convertToHtml($this->about),
            'footerHtml' => $markdown->convertToHtml($this->footer),
            'authorName' => $this->user?->name,
            'authorEmail' => $this->user?->email,
            'is_multi_author' => (bool) $this->is_multi_author,
            'sidebar' => (int) ($this->sidebar ?? 0),
        ];
    }
}
