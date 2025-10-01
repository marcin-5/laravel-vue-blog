<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class BlogService
{
    public function getUserBlogs(User $user): Collection
    {
        return Blog::query()
            ->where('user_id', $user->id)
            ->withPostsForIndex()
            ->withCategories()
            ->orderByDesc('created_at')
            ->get([
                'id',
                'user_id',
                'name',
                'slug',
                'description',
                'is_published',
                'locale',
                'sidebar',
                'page_size',
                'created_at'
            ]);
    }

    public function getCategories(): Collection
    {
        return Category::query()
            ->orderBy('slug')
            ->get(['id', 'name']);
    }

    public function createBlog(array $blogData, array $categories = []): Blog
    {
        $blog = Blog::create($blogData);

        if (!empty($categories)) {
            $blog->categories()->sync($categories);
        }

        return $blog;
    }

    public function updateBlog(Blog $blog, array $blogData, ?array $categories = null): Blog
    {
        $blog->fill($blogData);
        $blog->save();

        if ($categories !== null) {
            $blog->categories()->sync($categories);
        }

        return $blog;
    }
}
