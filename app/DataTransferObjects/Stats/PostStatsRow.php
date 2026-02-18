<?php

namespace App\DataTransferObjects\Stats;

readonly class PostStatsRow
{
    public function __construct(
        public int $postId,
        public string $title,
        public int $views,
        public int $uniqueViews,
        public int $botViews,
        public int $anonymousViews,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            postId: (int)$row->post_id,
            title: (string)$row->title,
            views: (int)$row->views,
            uniqueViews: (int)$row->unique_views,
            botViews: (int)($row->bot_views ?? 0),
            anonymousViews: (int)($row->anonymous_views ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'post_id' => $this->postId,
            'title' => $this->title,
            'views' => $this->views,
            'unique_views' => $this->uniqueViews,
            'bot_views' => $this->botViews,
            'anonymous_views' => $this->anonymousViews,
        ];
    }
}
