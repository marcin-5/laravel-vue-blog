<?php

namespace App\Observers;

use App\Models\Blog;
use Illuminate\Support\Str;

class BlogObserver
{
    public function creating(Blog $blog): void
    {
        $this->ensureSlug($blog);
    }

    public function updating(Blog $blog): void
    {
        if ($blog->isDirty('name') || empty($blog->slug)) {
            $this->ensureSlug($blog, $blog->id);
        }
    }

    private function ensureSlug(Blog $blog, ?int $ignoreId = null): void
    {
        $base = Str::slug($blog->name ?: 'blog');
        $slug = $base ?: 'blog';
        $i = 1;
        $query = Blog::query();
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        while ($query->clone()->where('slug', $slug)->exists()) {
            $slug = ($base ?: 'blog') . '-' . $i++;
        }
        $blog->slug = $slug;
    }
}
