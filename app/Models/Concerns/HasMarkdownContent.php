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
        $content = (string)($this->content ?? '');
        if ($content === '') {
            return '';
        }
        $parser = new ParsedownExtra();
        if (method_exists($parser, 'setSafeMode')) {
            $parser->setSafeMode(false);
        }
        $html = $parser->text($content);
        $purifier = new HTMLPurifier(HTMLPurifier_Config::createDefault());

        return $purifier->purify($html);
    }
}
