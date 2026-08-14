<?php

namespace App\Http\Resources;

use App\Services\MottoSelector;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicBlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'main_domain' => $this->main_domain,
            'url' => $this->public_url,
            'motto' => $this->motto,
            'displayedMotto' => app(MottoSelector::class)->select($this->motto),
            'theme' => $this->theme,
        ];
    }
}
