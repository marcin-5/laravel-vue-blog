<?php

namespace App\Services;

use Parsedown;

class MarkdownService
{
    private Parsedown $parsedown;

    public function __construct()
    {
        $this->parsedown = new Parsedown();
        $this->parsedown->setSafeMode(true);
    }

    public function convertToHtml(string $markdown): string
    {
        return $this->parsedown->text($markdown);
    }

    public function preview(string $content): array
    {
        return [
            'html' => $this->convertToHtml($content),
        ];
    }
}