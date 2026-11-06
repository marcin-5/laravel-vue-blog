<?php

declare(strict_types=1);

namespace App\Queries\Public;

use App\Models\Blog;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;

class PublicBlogPostsQuery
{
    /**
     * @param  Collection<int, Tag>  $tags
     */
    public function handle(Blog $blog, Collection $tags, string $operator = 'AND'): LengthAwarePaginator
    {
        $size = Number::clamp(
            (int) ($blog->page_size ?? config('blog.default_page_size')),
            1,
            config('blog.max_page_size'),
        );

        $builder = $blog->posts()->forPublicListing()->with('tags');

        if ($tags->isNotEmpty()) {
            $builder->whereHas(
                'tags',
                fn($query) => $query->whereIn('tags.id', $tags->modelKeys()),
                '>=',
                $operator === 'AND' ? $tags->count() : 1,
            );
        }

        return $builder
            ->paginate($size)
            ->withQueryString();
    }
}
