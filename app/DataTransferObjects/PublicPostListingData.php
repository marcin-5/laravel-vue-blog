<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class PublicPostListingData
{
    /**
     * @param  array{id: int, name: string, slug: string}|null  $activeTag
     */
    public function __construct(
        public AnonymousResourceCollection $posts,
        public array $pagination,
        public ?array $activeTag,
        public AnonymousResourceCollection $allTags,
    ) {}

    /**
     * @return array{posts: AnonymousResourceCollection, pagination: array, activeTag: ?array, allTags: AnonymousResourceCollection}
     */
    public function toArray(): array
    {
        return [
            'posts' => $this->posts,
            'pagination' => $this->pagination,
            'activeTag' => $this->activeTag,
            'allTags' => $this->allTags,
        ];
    }
}
