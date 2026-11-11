<?php

namespace App\Models\Concerns;

use HTMLPurifier;
use HTMLPurifier_Config;
use ParsedownExtra;
use RuntimeException;

trait HasMarkdownContent
{
    /**
     * Accessor to get Markdown content rendered as HTML.
     */
    public function getContentHtmlAttribute(): string
    {
        return $this->renderMarkdown((string) ($this->content ?? ''));
    }

    /**
     * Helper to render markdown content to HTML.
     */
    protected function renderMarkdown(string $content): string
    {
        if ($content === '') {
            return '';
        }

        $parser = new ParsedownExtra;
        if (method_exists($parser, 'setSafeMode')) {
            $parser->setSafeMode(false);
        }

        $html = $parser->text($content);
        $cachePath = config('app.htmlpurifier.cache_path');
        if (!is_dir($cachePath)) {
            if (!mkdir($cachePath, 0775, true) && !is_dir($cachePath)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $cachePath));
            }
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cachePath);
        $config->set('HTML.SafeIframe', false);
        $config->set('URI.SafeIframeRegexp', null);
        $config->set('CSS.AllowedProperties', []);
        $config->set('HTML.TargetBlank', false);
        $config->set('Attr.EnableID', true);
        return new HTMLPurifier($config)->purify($html);
    }

    /**
     * Accessor to get Markdown footer rendered as HTML.
     */
    public function getFooterHtmlAttribute(): string
    {
        return $this->renderMarkdown((string) ($this->footer ?? ''));
    }
}
