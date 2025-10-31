<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
use ParsedownExtra;

class MarkdownService
{
    private ParsedownExtra $parsedown;

    private HTMLPurifier $purifier;

    public function __construct()
    {
        $this->parsedown = new ParsedownExtra;
        // Allow raw HTML in Markdown (we will sanitize with HTMLPurifier afterwards)
        if (method_exists($this->parsedown, 'setSafeMode')) {
            $this->parsedown->setSafeMode(false);
        }

        // Configure a conservative purifier profile to prevent XSS while allowing common formatting
        $config = HTMLPurifier_Config::createDefault();
        // You can adjust allowed elements/attributes as needed; defaults are fairly safe
        // e.g., allow basic formatting and links/images
        $config->set('HTML.SafeIframe', false);
        $config->set('URI.SafeIframeRegexp', null);
        // Trust no CSS by default
        $config->set('CSS.AllowedProperties', []);
        $config->set('HTML.TargetBlank', true);
        $this->purifier = new HTMLPurifier($config);
    }

    public function preview(string $content): array
    {
        return [
            'html' => $this->convertToHtml($content),
        ];
    }

    public function convertToHtml(?string $markdown): string
    {
        if ($markdown === null) {
            return '';
        }

        $html = $this->parsedown->text($markdown);

        return $this->purifier->purify($html);
    }
}
