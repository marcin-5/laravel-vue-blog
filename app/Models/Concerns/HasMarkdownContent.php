<?php

namespace App\Models\Concerns;

use HTMLPurifier;
use HTMLPurifier_Config;
use ParsedownExtra;

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
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.SafeIframe', false);
        $config->set('URI.SafeIframeRegexp', null);
        $config->set('CSS.AllowedProperties', []);
        $config->set('HTML.TargetBlank', true);
        $config->set('Attr.EnableID', true);
        $purifier = new HTMLPurifier($config);

        return $purifier->purify($html);
    }

    /**
     * Accessor to get Markdown footer rendered as HTML.
     */
    public function getFooterHtmlAttribute(): string
    {
        return $this->renderMarkdown((string) ($this->footer ?? ''));
    }
}
