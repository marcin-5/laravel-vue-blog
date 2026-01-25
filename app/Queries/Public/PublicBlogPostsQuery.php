<?php

namespace App\Queries\Public;

use App\Models\Blog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Number;

class PublicBlogPostsQuery
{
    public function handle(Blog $blog): LengthAwarePaginator
    {
        $size = Number::clamp(
            (int)($blog->page_size ?? config('blog.default_page_size')),
            1,
            config('blog.max_page_size'),
        );

        return $blog->posts()
            ->forPublicListing()
            ->paginate($size)
            ->withQueryString();
    }
}
