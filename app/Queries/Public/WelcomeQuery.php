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
    public function handle(Request $request): array
    {
        $locale = app()->getLocale();
        $selectedCategoryIds = $this->getSelectedCategoryIds($request);

        $blogs = $this->getWelcomeBlogs($selectedCategoryIds, $locale);
        $categories = $this->getWelcomeCategories($locale);

        return [
            'blogs' => $blogs,
            'categories' => $categories,
            'selectedCategoryIds' => $selectedCategoryIds,
            'locale' => $locale,
        ];
    }

    private function getSelectedCategoryIds(Request $request): array
    {
        $selected = $request->query('categories', []);

        if (is_string($selected)) {
            $selected = explode(',', $selected);
        }

        return collect($selected)
            ->map(fn($value) => (int)trim($value))
            ->filter()
            ->values()
            ->all();
    }

    private function getWelcomeBlogs(array $selectedCategoryIds, string $locale): Collection
    {
        $categoryFilter = empty($selectedCategoryIds) ? 'all' : implode(',', $selectedCategoryIds);
        $blogsCacheKey = "welcome_blogs_{$locale}_{$categoryFilter}";
        $ttl = app()->isLocal() ? 5 : 3600;

        return Cache::remember($blogsCacheKey, $ttl, function () use ($selectedCategoryIds) {
            $blogsQuery = Blog::query()
                ->where('is_published', true)
                ->with([
                    'categories' => fn($q) => $q->select(['categories.id', 'categories.slug', 'categories.name']),
                    'user' => fn($q) => $q->select(['id', 'name']),
                ])
                ->select(['id', 'name', 'slug', 'description', 'locale', 'user_id']);

            if (!empty($selectedCategoryIds)) {
                $blogsQuery->whereHas('categories', function ($q) use ($selectedCategoryIds) {
                    $q->whereIn('categories.id', $selectedCategoryIds);
                });
            }

            return $blogsQuery
                ->addSelect([
                    'latest_post_at' => function ($query) {
                        $query->selectRaw('COALESCE(MAX(COALESCE(published_at, created_at)), NULL)')
                            ->from('posts')
                            ->whereColumn('posts.blog_id', 'blogs.id')
                            ->where('is_published', true);
                    },
                ])
                ->orderByDesc('latest_post_at')
                ->orderBy('name')
                ->get()
                ->map(fn(Blog $b) => (new WelcomeBlogResource($b))->resolve())
                ->values();
        });
    }

    private function getWelcomeCategories(string $locale): Collection
    {
        $cacheKey = "welcome_categories_{$locale}";

        return Cache::remember($cacheKey, 3600, function () use ($locale) {
            return Category::query()
                ->select(['id', 'slug', 'name'])
                ->orderBy('name->' . $locale)
                ->get()
                ->map(fn(Category $c) => [
                    'id' => $c->id,
                    'slug' => $c->slug,
                    'name' => $c->getTranslation('name', $locale) ?? $c->slug,
                ])
                ->values();
        });
    }
}
