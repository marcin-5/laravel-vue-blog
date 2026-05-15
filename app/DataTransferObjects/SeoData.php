<?php

namespace App\DataTransferObjects;

readonly class SeoData
{
    public function __construct(
        public string $title,
        public string $description,
        public string $canonicalUrl,
        public string $ogImage,
        public string $ogType,
        public string $locale,
        public array $structuredData,
        public ?string $publishedTime = null,
        public ?string $modifiedTime = null,
        public ?array $alternateLinks = null,
        public ?string $robots = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'canonicalUrl' => $this->canonicalUrl,
            'ogImage' => $this->ogImage,
            'ogType' => $this->ogType,
            'locale' => $this->locale,
            'structuredData' => $this->structuredData,
            'publishedTime' => $this->publishedTime,
            'modifiedTime' => $this->modifiedTime,
            'alternateLinks' => $this->alternateLinks,
            'robots' => $this->robots,
        ], fn($value) => $value !== null);
    }
}
