<?php

namespace App\Queries\Public;

use App\Http\Resources\WelcomeBlogResource;
use App\Models\Blog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class WelcomeQuery
{
    /**
     * Handle the welcome query logic.
     *
     * @return array<string, mixed>
     */
    public function handle(Request $request): array
    {
        $locale = app()->getLocale();
        $selectedCategoryIds = $this->getSelectedCategoryIds($request);

        return [
            'blogs' => $this->getWelcomeBlogs($selectedCategoryIds, $locale),
            'categories' => $this->getWelcomeCategories($locale),
            'selectedCategoryIds' => $selectedCategoryIds,
            'locale' => $locale,
        ];
    }

    /**
     * Parse selected category IDs from the request.
     *
     * @return array<int>
     */
    private function getSelectedCategoryIds(Request $request): array
    {
        $selected = $request->query('categories', []);

        return collect(is_string($selected) ? explode(',', $selected) : $selected)
            ->map(fn($value) => (int) trim($value))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Get welcome blogs based on selected categories and locale.
     */
    private function getWelcomeBlogs(array $selectedCategoryIds, string $locale): Collection
    {
        $categoryFilter = empty($selectedCategoryIds) ? 'all' : implode(',', $selectedCategoryIds);
        $cacheKey = "welcome_blogs_{$locale}_{$categoryFilter}";
        $ttl = app()->isLocal() ? 5 : 3600;

        return Cache::remember($cacheKey, $ttl, function () use ($selectedCategoryIds) {
            $blogs = Blog::query()
                ->where('is_published', true)
                ->with([
                    'categories:id,slug,name',
                    'user:id,name',
                ])
                ->select(['id', 'name', 'slug', 'description', 'locale', 'user_id'])
                ->when(!empty($selectedCategoryIds), function ($query) use ($selectedCategoryIds) {
                    $query->whereHas('categories', fn($q) => $q->whereIn('categories.id', $selectedCategoryIds));
                })
                ->orderByLatestPost()
                ->get();

            return collect(WelcomeBlogResource::collection($blogs)->resolve());
        });
    }

    /**
     * Get all categories for the welcome filter.
     */
    private function getWelcomeCategories(string $locale): Collection
    {
        $cacheKey = "welcome_categories_{$locale}";

        return Cache::remember($cacheKey, 3600, function () use ($locale) {
            return Category::query()
                ->select(['id', 'slug', 'name'])
                ->orderBy("name->{$locale}")
                ->get()
                ->map(fn(Category $c) => [
                    'id' => $c->id,
                    'slug' => $c->slug,
                    'name' => $c->getTranslation('name', $locale) ?: $c->slug,
                ])
                ->values();
        });
    }
}
