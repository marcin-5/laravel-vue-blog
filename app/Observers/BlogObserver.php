<?php

namespace App\Observers;

use App\Models\Blog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BlogObserver
{
    public function creating(Blog $blog): void
    {
        if (empty($blog->slug)) {
            $this->ensureSlug($blog);
        }
    }

    public function updating(Blog $blog): void
    {
        if ($blog->isDirty('name') || empty($blog->slug)) {
            $this->ensureSlug($blog, $blog->id);
        }
    }

    public function created(Blog $blog): void
    {
        $this->clearWelcomeCache();
    }

    public function updated(Blog $blog): void
    {
        $this->clearWelcomeCache();
    }

    public function deleted(Blog $blog): void
    {
        $this->clearWelcomeCache();
    }

    private function ensureSlug(Blog $blog, ?int $ignoreId = null): void
    {
        $reserved = [
            'admin',
            'api',
            'dashboard',
            'settings',
            '_',
            'login',
            'register',
            'logout',
            'password',
            'email',
            'verify',
            'about',
            'contact',
            'newsletter',
            'enneagram-test',
            'www',
            'robots.txt',
            'sitemap.xml',
            'home',
            'public',
            'assets',
            'build',
            'storage',
            'vendor',
        ];

        $base = Str::slug($blog->name ?: 'blog');

        if (in_array($base, $reserved)) {
            $base = $base . '-blog';
        }

        $slug = $base ?: 'blog';
        $i = 1;
        $query = Blog::query();
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        while ($query->clone()->where('slug', $slug)->exists() || in_array($slug, $reserved)) {
            $slug = ($base ?: 'blog') . '-' . $i++;
        }
        $blog->slug = $slug;
    }

    private function clearWelcomeCache(): void
    {
        // Clear all welcome page caches (categories and blogs for all locales and filters)
        Cache::flush();
    }
}
