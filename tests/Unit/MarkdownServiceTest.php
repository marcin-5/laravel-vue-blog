<?php

use App\Services\MarkdownService;

it('converts null to empty string', function () {
    $service = new MarkdownService;

    $result = $service->convertToHtml(null);

    expect($result)->toBe('');
});

it('converts empty string to empty string', function () {
    $service = new MarkdownService;

    $result = $service->convertToHtml('');

    expect($result)->toBe('');
});

it('converts basic markdown to html', function () {
    $service = new MarkdownService;

    $result = $service->convertToHtml('**bold**');

    expect($result)->toContain('<strong>bold</strong>');
});

it('converts markdown with links', function () {
    $service = new MarkdownService;

    $result = $service->convertToHtml('[link](https://example.com)');

    expect($result)->toContain('<a')
        ->toContain('href="https://example.com"')
        ->toContain('link</a>');
});
