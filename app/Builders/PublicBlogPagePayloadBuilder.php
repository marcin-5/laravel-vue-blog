<?php

declare(strict_types=1);

namespace App\Builders;

use App\DataTransferObjects\PublicBlogChromeData;
use App\DataTransferObjects\PublicPostListingData;
use App\Http\Controllers\Concerns\FormatsPaginator;
use App\Http\Resources\PublicPostResource;
use App\Http\Resources\TagResource;
use App\Models\Blog;
use App\Models\Tag;
use App\Services\MarkdownService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class PublicBlogPagePayloadBuilder
{
    use FormatsPaginator;

    public function __construct(
        private MarkdownService $markdown,
    ) {}

    /**
     * Build the chrome (layout) part of a public blog page payload.
     *
     * @param  array<string, mixed>|null  $navigation
     */
    public function buildChrome(Blog $blog, ?array $navigation, bool $withFooter = true): PublicBlogChromeData
    {
        return new PublicBlogChromeData(
            locale: app()->getLocale(),
            sidebar: (int) ($blog->sidebar ?? 0),
            sidebarPosition: $blog->sidebar_position,
            navigation: $navigation,
            footerHtml: $withFooter ? $this->markdown->convertToHtml($blog->footer) : null,
        );
    }

    /**
     * Build the posts listing part of a public blog page payload.
     */
    public function buildListing(Blog $blog, LengthAwarePaginator $paginator, ?Tag $tag = null): PublicPostListingData
    {
        return new PublicPostListingData(
            posts: PublicPostResource::collection($paginator->items()),
            pagination: $this->formatPagination($paginator),
            activeTag: $tag ? [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ] : null,
            allTags: TagResource::collection($blog->tags->sortBy('name')->values()),
        );
    }
}
