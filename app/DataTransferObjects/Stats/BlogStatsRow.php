<?php

namespace App\DataTransferObjects\Stats;

readonly class BlogStatsRow
{
    public function __construct(
        public int $blogId,
        public string $name,
        public int $ownerId,
        public string $ownerName,
        public int $views,
        public int $uniqueViews,
        public int $postViews,
        public int $uniquePostViews,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            blogId: (int)$row->blog_id,
            name: (string)$row->name,
            ownerId: (int)$row->owner_id,
            ownerName: (string)($row->owner_name ?? ''),
            views: (int)$row->views,
            uniqueViews: (int)($row->unique_views ?? 0),
            postViews: (int)$row->post_views,
            uniquePostViews: (int)($row->unique_post_views ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'blog_id' => $this->blogId,
            'name' => $this->name,
            'owner_id' => $this->ownerId,
            'owner_name' => $this->ownerName,
            'views' => $this->views,
            'unique_views' => $this->uniqueViews,
            'post_views' => $this->postViews,
            'unique_post_views' => $this->uniquePostViews,
        ];
    }
}
