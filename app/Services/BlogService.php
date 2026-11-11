<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Blog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

readonly class BlogService
{
    private const array BLOG_FIELDS = [
        'id',
        'user_id',
        'name',
        'slug',
        'description',
        'motto',
        'footer',
        'is_published',
        'allow_multiple_tag_filtering',
        'locale',
        'sidebar',
        'page_size',
        'theme',
        'seo_title',
        'seo_description',
        'about_seo_description',
        'contact_seo_description',
        'about',
        'created_at',
    ];

    public function getUserBlogs(User $user): Collection
    {
        $blogs = Blog::query()
            ->where('user_id', $user->id)
            ->with(['landingPage:blog_id,content'])
            ->withPostsForIndex()
            ->withCategories()
            ->orderByDesc('created_at')
            ->get(self::BLOG_FIELDS);

        $blogs->each(fn(Blog $blog) => $blog->append('main_domain'));

        return $blogs;
    }

    public function getCategories(): Collection
    {
        return Category::query()
            ->orderBy('slug')
            ->get(['id', 'name']);
    }

    public function createBlog(array $blogData, array $categories = []): Blog
    {
        $landingContent = $this->extractLandingContent($blogData);
        $blog = Blog::create($blogData);
        $this->updateLandingPage($blog, $landingContent);
        $this->syncCategories($blog, $categories);
        return $blog;
    }

    private function extractLandingContent(array &$blogData): ?string
    {
        if (!array_key_exists('landing_content', $blogData)) {
            return null;
        }
        $landingContent = $blogData['landing_content'];
        unset($blogData['landing_content']);
        return $landingContent;
    }

    private function updateLandingPage(Blog $blog, ?string $content): void
    {
        if ($content === null) {
            return;
        }
        $blog->landingPage()->updateOrCreate(
            ['blog_id' => $blog->id],
            ['content' => $content],
        );
    }

    private function syncCategories(Blog $blog, array $categories): void
    {
        $blog->categories()->sync($categories);
    }

    public function updateBlog(Blog $blog, array $blogData, ?array $categories = null): Blog
    {
        $landingContent = $this->extractLandingContent($blogData);
        $blog->fill($blogData);
        $blog->save();
        $this->updateLandingPage($blog, $landingContent);
        $this->syncCategories($blog, $categories ?? []);
        return $blog;
    }
}
