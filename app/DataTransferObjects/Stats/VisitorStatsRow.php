<?php

namespace App\DataTransferObjects\Stats;

readonly class VisitorStatsRow
{
    public function __construct(
        public string $visitorLabel,
        public int $blogViews,
        public int $postViews,
        public int $views,
        public int $lifetimeViews,
        public ?string $userAgent,
        public ?string $lastSeenAt = null,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            visitorLabel: (string)$row->visitor_label,
            blogViews: (int)$row->blog_views,
            postViews: (int)$row->post_views,
            views: (int)$row->views,
            lifetimeViews: (int)$row->lifetime_views,
            userAgent: isset($row->user_agent) ? (string)$row->user_agent : null,
            lastSeenAt: isset($row->last_seen_at) ? (string)$row->last_seen_at : null,
        );
    }

    public function toArray(): array
    {
        return [
            'visitor_label' => $this->visitorLabel,
            'blog_views' => $this->blogViews,
            'post_views' => $this->postViews,
            'views' => $this->views,
            'lifetime_views' => $this->lifetimeViews,
            'user_agent' => $this->userAgent,
            'last_seen_at' => $this->lastSeenAt,
        ];
    }
}
