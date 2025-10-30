<?php

namespace App\Http\Resources;

use App\Services\MarkdownService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WelcomeBlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $blogLocale = $this->locale ?: app()->getLocale();
        $descriptionHtml = '';

        if (!empty($this->description)) {
            $descriptionHtml = app(MarkdownService::class)->convertToHtml($this->description);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'author' => $this->user?->name ?? '',
            'descriptionHtml' => $descriptionHtml,
            'categories' => $this->categories
                ->filter(fn($c) => method_exists($c, 'hasTranslation') ? $c->hasTranslation('name', $blogLocale) : true)
                ->map(fn($c) => [
                    'id' => $c->id,
                    'slug' => $c->slug,
                    'name' => $c->getTranslation('name', $blogLocale) ?? $c->slug,
                ])->values(),
        ];
    }
}
