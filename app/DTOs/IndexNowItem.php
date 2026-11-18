<?php

namespace App\DTOs;

readonly class IndexNowItem
{
    public function __construct(
        public string $url,
        public ?int $blogId = null,
        public ?int $postId = null,
    ) {}
}
