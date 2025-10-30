<?php

namespace App\DataTransferObjects;

class SeoData
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $canonicalUrl,
        public readonly string $ogImage,
        public readonly string $ogType,
        public readonly string $locale,
        public readonly array $structuredData,
        public readonly ?string $publishedTime = null,
        public readonly ?string $modifiedTime = null,
    ) {
    }

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
        ], fn($value) => $value !== null);
    }
}
