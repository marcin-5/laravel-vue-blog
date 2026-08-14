<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

readonly class PublicBlogChromeData
{
    public function __construct(
        public string $locale,
        public int $sidebar,
        public ?string $sidebarPosition,
        public ?array $navigation,
        public ?string $footerHtml,
    ) {}

    /**
     * @return array{locale: string, sidebar: int, sidebarPosition: ?string, navigation: ?array, footerHtml: ?string}
     */
    public function toArray(): array
    {
        return [
            'locale' => $this->locale,
            'sidebar' => $this->sidebar,
            'sidebarPosition' => $this->sidebarPosition,
            'navigation' => $this->navigation,
            'footerHtml' => $this->footerHtml,
        ];
    }
}
