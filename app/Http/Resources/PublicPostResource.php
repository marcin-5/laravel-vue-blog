<?php

namespace App\Http\Resources;

use App\Http\Controllers\Concerns\FormatsDatesForLocale;
use App\Services\MarkdownService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicPostResource extends JsonResource
{
    use FormatsDatesForLocale;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => app(MarkdownService::class)->convertToHtml($this->excerpt),
            'published_at' => $this->published_at?->format('Y-m-d'),
        ];
    }
}
