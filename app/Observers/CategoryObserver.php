<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryObserver
{
    public function creating(Category $category): void
    {
        $this->ensureSlug($category);
    }

    public function updating(Category $category): void
    {
        if ($category->isDirty('name') || empty($category->slug)) {
            $this->ensureSlug($category, $category->id);
        }
    }

    public function created(Category $category): void
    {
        $this->clearWelcomeCache();
    }

    public function updated(Category $category): void
    {
        $this->clearWelcomeCache();
    }

    public function deleted(Category $category): void
    {
        $this->clearWelcomeCache();
    }

    private function ensureSlug(Category $category, ?int $ignoreId = null): void
    {
        $base = Str::slug($category->name ?: 'category');
        $slug = $base ?: 'category';
        $i = 1;
        $query = Category::query();
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        while ($query->clone()->where('slug', $slug)->exists()) {
            $slug = ($base ?: 'category') . '-' . $i++;
        }
        $category->slug = $slug;
    }

    private function clearWelcomeCache(): void
    {
        // Clear all welcome page caches (categories and blogs for all locales and filters)
        Cache::flush();
    }
}
