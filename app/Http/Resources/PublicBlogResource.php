<?php

namespace App\Http\Resources;

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
            'theme' => $this->theme,
        ];
    }
}
