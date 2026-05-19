<?php

declare(strict_types=1);

namespace App\Queries\Public;

use App\Models\Blog;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Number;

class PublicBlogPostsQuery
{
    public function handle(Blog $blog, ?Tag $tag = null): LengthAwarePaginator
    {
        $size = Number::clamp(
            (int) ($blog->page_size ?? config('blog.default_page_size')),
            1,
            config('blog.max_page_size'),
        );

        $builder = $blog->posts()->forPublicListing();

        if ($tag !== null) {
            $builder->whereHas('tags', fn($q) => $q->whereKey($tag->id));
        }

        return $builder
            ->paginate($size)
            ->withQueryString();
    }
}
