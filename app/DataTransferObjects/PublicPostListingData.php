<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class PublicPostListingData
{
    /**
     * @param  array{id: int, name: string, slug: string}|null  $activeTag
     * @param  list<array{id: int, name: string, slug: string}>  $activeTags
     */
    public function __construct(
        public AnonymousResourceCollection $posts,
        public array $pagination,
        public ?array $activeTag,
        public array $activeTags,
        public string $operator,
        public AnonymousResourceCollection $allTags,
    ) {}

    /**
     * @return array{posts: AnonymousResourceCollection, pagination: array, activeTag: ?array, activeTags: list<array{id: int, name: string, slug: string}>, operator: string, allTags: AnonymousResourceCollection}
     */
    public function toArray(): array
    {
        return [
            'posts' => $this->posts,
            'pagination' => $this->pagination,
            'activeTag' => $this->activeTag,
            'activeTags' => $this->activeTags,
            'operator' => $this->operator,
            'allTags' => $this->allTags,
        ];
    }
}
